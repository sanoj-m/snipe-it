<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIncidentTicketRequest;
use App\Http\Requests\UpdateIncidentTicketRequest;
use App\Models\Asset;
use App\Models\IncidentTicket;
use App\Models\MaintenanceType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class IncidentDeskController extends Controller
{
    /**
     * Category → task-type taxonomy for the incident desk. Kept verbatim from
     * the standalone tool this page replaces.
     */
    public const TASK_CATEGORIES = [
        'User Management' => ['User Onboarding', 'User Offboarding', 'Microsoft 365 User Creation', 'Active Directory User Creation', 'Password Reset / Change'],
        'Email' => ['Outlook Crash / Troubleshooting', 'Shared Mailbox Creation', 'Group Email Creation', 'Email Signature Issue', 'Email Account Issue'],
        'Software' => ['Windows Update', 'Autodesk Update', 'Adobe Update / Troubleshooting', 'NVIDIA Driver Update', 'Enscape Update / Troubleshooting', 'Software Installation', 'Software Troubleshooting', 'License Assignment/Access'],
        'Hardware' => ['Setup PC', 'PC Reimage', 'PC Hardware / Performance Issue', 'PC Relocation', 'Printer Toner Replacement', 'Printer / Printing Issue'],
        'Microsoft 365 / Cloud' => ['Microsoft 365 User Creation', 'Access / Permission Request', 'Shared Mailbox Creation'],
        'File & Storage' => ['Project Folder Creation', 'Shared Folder Creation', 'Dropbox Folder Creation', 'File / Folder Access Request'],
        'Network & Security' => ['Network / Internet Issue', 'Mapping Network Drive', 'Antivirus / Security Issue'],
        'Meeting Rooms' => ['Meeting Room Assistance'],
        'Other' => ['Other IT Support Request'],
    ];

    public function index(): View
    {
        $this->authorize('view', Asset::class);

        $taskCategories = self::TASK_CATEGORIES;

        // Any maintenance type added via /maintenance-types that isn't in
        // the desk's built-in taxonomy shows up under "Other".
        $mapped = collect($taskCategories)->flatten()->all();
        $extraTypes = MaintenanceType::orderBy('name')
            ->pluck('name')
            ->reject(fn ($name) => in_array($name, $mapped, true))
            ->values()
            ->all();

        if ($extraTypes !== []) {
            $taskCategories['Other'] = array_values(array_unique(array_merge($taskCategories['Other'], $extraTypes)));
        }

        $requesters = IncidentTicket::query()
            ->whereNotNull('requester_name')
            ->distinct()
            ->orderBy('requester_name')
            ->pluck('requester_name');

        $userNames = User::select('first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (User $user) => trim($user->first_name.' '.$user->last_name))
            ->filter();

        $requesters = $requesters->merge($userNames)->unique(fn ($name) => mb_strtolower($name))->sort()->values();

        return view('maintenances.incident-desk', [
            'taskCategories' => $taskCategories,
            'requesters' => $requesters,
        ]);
    }

    public function tickets(): JsonResponse
    {
        $this->authorize('view', Asset::class);

        $tickets = IncidentTicket::with('asset:id,asset_tag,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (IncidentTicket $ticket) => $this->serializeTicket($ticket));

        return response()->json(['tickets' => $tickets]);
    }

    public function store(StoreIncidentTicketRequest $request): JsonResponse
    {
        $this->authorize('update', Asset::class);

        $data = $request->validated();
        $requester = trim((string) ($data['requester_name'] ?? ''));

        $ticket = new IncidentTicket;
        $ticket->requester_name = $requester !== '' ? $requester : null;
        $ticket->requester_user_id = IncidentTicket::resolveRequesterUserId($requester);
        $ticket->category = $data['category'];
        $ticket->task_type = $data['task_type'];
        $ticket->issue = trim((string) ($data['issue'] ?? '')) ?: null;
        $ticket->status = $data['status'];
        $ticket->asset_id = $data['asset_id'] ?? null;
        $ticket->details = self::combineDetails($ticket->requester_name, $ticket->task_type, $ticket->issue);
        $ticket->created_by = (int) auth()->id();
        $ticket->save();

        return response()->json(['ticket' => $this->serializeTicket($ticket->load('asset:id,asset_tag,name'))], 201);
    }

    public function update(UpdateIncidentTicketRequest $request, IncidentTicket $ticket): JsonResponse
    {
        $this->authorize('update', Asset::class);

        $data = $request->validated();

        if (array_key_exists('requester_name', $data)) {
            $requester = trim((string) $data['requester_name']);
            $ticket->requester_name = $requester !== '' ? $requester : null;
            $ticket->requester_user_id = IncidentTicket::resolveRequesterUserId($requester);
        }

        foreach (['category', 'task_type', 'issue', 'status', 'asset_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $ticket->{$field} = is_string($data[$field]) ? (trim($data[$field]) ?: null) : $data[$field];
            }
        }

        if (array_key_exists('details', $data)) {
            $ticket->details = trim($data['details']);
        } else {
            $ticket->details = self::combineDetails($ticket->requester_name, $ticket->task_type, $ticket->issue);
        }

        $ticket->save();

        return response()->json(['ticket' => $this->serializeTicket($ticket->load('asset:id,asset_tag,name'))]);
    }

    public function destroy(IncidentTicket $ticket): JsonResponse
    {
        $this->authorize('update', Asset::class);

        $ticket->delete();

        return response()->json(['status' => 'ok']);
    }

    /**
     * The user's currently checked-out device (most recent checkout), used
     * by the file-a-ticket form to pre-fill the device picker.
     */
    public function userDevice(User $user): JsonResponse
    {
        $this->authorize('view', Asset::class);

        $asset = Asset::where('assigned_to', $user->id)
            ->where('assigned_type', User::class)
            ->orderByDesc('last_checkout')
            ->orderByDesc('updated_at')
            ->with('model')
            ->first();

        return response()->json([
            'device' => $asset ? ['id' => $asset->id, 'text' => $asset->present()->fullName] : null,
        ]);
    }

    private function serializeTicket(IncidentTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'display_id' => 'INC-'.str_pad((string) ($ticket->legacy_id ?? $ticket->id), 4, '0', STR_PAD_LEFT),
            'requester_name' => $ticket->requester_name,
            'requester_user_id' => $ticket->requester_user_id,
            'asset_id' => $ticket->asset_id,
            'asset_label' => $ticket->asset ? trim($ticket->asset->asset_tag.' '.$ticket->asset->name) : null,
            'category' => $ticket->category,
            'task_type' => $ticket->task_type,
            'issue' => $ticket->issue,
            'details' => $ticket->details,
            'status' => $ticket->status,
            'created_at' => $ticket->created_at?->toIso8601String(),
        ];
    }

    /**
     * Build the combined summary line the standalone tool used:
     * "Requester - Task type: notes".
     */
    public static function combineDetails(?string $requester, ?string $taskType, ?string $issue): string
    {
        $combined = $requester ? $requester.' - '.(string) $taskType : (string) $taskType;

        if ($issue) {
            $combined .= ': '.$issue;
        }

        return trim($combined);
    }
}
