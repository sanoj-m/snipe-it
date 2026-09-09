<?php

namespace SnipeIt\FloatingLicenses\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseAllocation;
use SnipeIt\FloatingLicenses\Models\FloatingLicenseConfig;
use SnipeIt\FloatingLicenses\Support\BulkUserAssignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LicenseUsersController extends Controller
{
    /**
     * Export the assigned users of a single license as CSV.
     *
     * Covers both assignment kinds: core seat checkouts and active floating
     * allocations (a license can carry both when it accumulated seat
     * checkouts before floating was enabled on it).
     */
    public function exportUsers(License $license, BulkUserAssignment $bulk): StreamedResponse
    {
        $this->authorize('view', $license);

        $filename = 'license-'.$license->id.'-users-'.date('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($license, $bulk) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['username', 'first_name', 'last_name', 'email', 'assignment_type', 'assigned_at']);

            foreach ($bulk->seatAssignedUsers($license) as $user) {
                $seat = LicenseSeat::where('license_id', $license->id)
                    ->where('assigned_to', $user->id)
                    ->whereNull('deleted_at')
                    ->first();

                fputcsv($out, [$user->username, $user->first_name, $user->last_name, $user->email, 'seat', $seat?->created_at]);
            }

            $allocations = FloatingLicenseAllocation::where('license_id', $license->id)->active()->get();
            foreach ($bulk->floatingAssignedUsers($license) as $user) {
                $allocation = $allocations->firstWhere('user_id', $user->id);
                fputcsv($out, [$user->username, $user->first_name, $user->last_name, $user->email, 'floating', $allocation?->allocated_at]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Import a list of users (username, email, or full name — one per line,
     * optional header row) and assign the license to each of them.
     *
     * Floating license -> pool allocation; standard license -> core seat
     * checkout. Users already holding the license are skipped, unknown
     * identifiers are reported, and a standard license that runs out of
     * seats reports the remainder as failed.
     */
    public function importUsers(Request $request, License $license, BulkUserAssignment $bulk): RedirectResponse
    {
        $this->authorize('checkout', $license);

        $request->validate([
            'user_list' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $identifiers = $this->parseIdentifiers($request->file('user_list')->getRealPath());

        if (count($identifiers) === 0) {
            return redirect()->route('licenses.show', $license)
                ->with('error', trans('floating-licenses::floating.message.import_empty'));
        }

        $userIds = [];
        $notFound = [];

        foreach ($identifiers as $identifier) {
            $user = User::where('username', $identifier)->first()
                ?? User::where('email', $identifier)->first()
                // Fall back to the full display name ("Daria Morrison") so
                // exports that carry names instead of usernames re-import.
                ?? User::whereRaw("TRIM(CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) = ?", [$identifier])->first();

            if ($user) {
                $userIds[] = $user->id;
            } else {
                $notFound[] = $identifier;
            }
        }

        $result = $bulk->addUsers($license, $userIds);

        return redirect()->route('licenses.show', $license)
            ->with(
                ($result['failed'] > 0 || count($notFound) > 0) ? 'warning' : 'success',
                trans('floating-licenses::floating.message.import_result', [
                    'added' => $result['added'],
                    'skipped' => $result['skipped'],
                    'failed' => $result['failed'],
                    'notfound' => count($notFound) ? implode(', ', array_slice($notFound, 0, 10)).(count($notFound) > 10 ? '...' : '') : '-',
                ])
            );
    }

    /**
     * Master export: every license with its full information plus one row
     * per assigned user (seat and floating). Licenses with no assigned
     * users still get a row with empty user columns.
     */
    public function exportFull(): StreamedResponse
    {
        $this->authorize('view', License::class);

        $filename = 'licenses-full-'.date('Y-m-d-His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'license_id', 'company', 'license_name', 'product_key', 'seats',
                'purchase_cost', 'purchase_date', 'expiration_date', 'manufacturer', 'category',
                'floating', 'pool_size', 'username', 'first_name', 'last_name', 'email', 'assignment_type',
            ]);

            License::with(['company', 'manufacturer', 'category'])
                ->orderBy('id')
                ->chunk(500, function ($licenses) use ($out) {
                    foreach ($licenses as $license) {
                        $config = FloatingLicenseConfig::where('license_id', $license->id)->first();

                        $base = [
                            $license->id,
                            $license->company?->name,
                            $license->name,
                            $license->serial,
                            $license->seats,
                            $license->purchase_cost,
                            $license->purchase_date?->format('Y-m-d'),
                            $license->expiration_date?->format('Y-m-d'),
                            $license->manufacturer?->name,
                            $license->category?->name,
                            $config ? 'yes' : 'no',
                            $config?->pool_size,
                        ];

                        $rows = [];

                        $seats = LicenseSeat::where('license_id', $license->id)
                            ->whereNotNull('assigned_to')
                            ->whereNull('deleted_at')
                            ->with('user')
                            ->get();
                        foreach ($seats as $seat) {
                            if (! $seat->user) {
                                continue;
                            }
                            $rows[] = array_merge($base, [$seat->user->username, $seat->user->first_name, $seat->user->last_name, $seat->user->email, 'seat']);
                        }

                        $allocations = FloatingLicenseAllocation::where('license_id', $license->id)
                            ->active()
                            ->with('user')
                            ->get();
                        foreach ($allocations as $allocation) {
                            if (! $allocation->user) {
                                continue;
                            }
                            $rows[] = array_merge($base, [$allocation->user->username, $allocation->user->first_name, $allocation->user->last_name, $allocation->user->email, 'floating']);
                        }

                        if (count($rows) === 0) {
                            $rows[] = array_merge($base, ['', '', '', '', '']);
                        }

                        foreach ($rows as $row) {
                            fputcsv($out, $row);
                        }
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Pull identifiers out of the uploaded CSV. Handles:
     *  - a UTF-8 BOM at the start of the file (Excel exports);
     *  - an optional header row (any of username/email/user/full name/name/
     *    display name) — when present, the most specific matching column is
     *    used, so a per-license export (username, first_name, ...) or a
     *    single-column name list both re-import cleanly;
     *  - plain one-identifier-per-line files (first column used).
     *
     * @return string[]
     */
    protected function parseIdentifiers(string $path): array
    {
        $identifiers = [];

        if (($handle = fopen($path, 'r')) === false) {
            return $identifiers;
        }

        $column = 0;
        $isFirstRow = true;
        $headerCandidates = ['username', 'email', 'user', 'full name', 'fullname', 'display name', 'name'];

        while (($row = fgetcsv($handle)) !== false) {
            if ($isFirstRow) {
                $isFirstRow = false;
                // Strip a UTF-8 BOM from the first cell before any comparison.
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) ($row[0] ?? ''));

                $cells = array_map(fn ($cell) => strtolower(trim((string) $cell)), $row);

                if (count(array_intersect($cells, $headerCandidates)) > 0) {
                    foreach ($headerCandidates as $preferred) {
                        $index = array_search($preferred, $cells, true);
                        if ($index !== false) {
                            $column = $index;
                            break;
                        }
                    }

                    continue;
                }
            }

            $value = trim((string) ($row[$column] ?? ''));

            if ($value === '' || in_array(strtolower($value), $headerCandidates, true)) {
                continue;
            }

            $identifiers[] = $value;
        }

        fclose($handle);

        return array_values(array_unique($identifiers));
    }
}
