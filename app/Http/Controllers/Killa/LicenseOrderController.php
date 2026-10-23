<?php

namespace App\Http\Controllers\Killa;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\License;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * [killa-ux] Drag-to-reorder endpoint for the licenses list. Recovered from
 * the deleted RenewalTrackerController@reorder (git e85d36876e), unchanged in
 * behaviour: sequential sort_order within the posted set, company-scoped.
 */
class LicenseOrderController extends Controller
{
    /**
     * [killa-ux] Flips the per-session new-design flag for the licenses
     * section (default off = classic bootstrap-table views) and redirects back.
     */
    public function toggleUx(Request $request): \Illuminate\Http\RedirectResponse
    {
        session(['killa_licenses_ux' => ! session('killa_licenses_ux', false)]);

        return redirect()->back();
    }

    public function reorder(Request $request): JsonResponse
    {
        if (! Gate::allows('update', License::class)) {
            abort(403);
        }

        $validated = $request->validate([
            'order' => 'required|array|min:1',
            'order.*' => 'integer|distinct',
        ]);

        $licenses = Company::scopeCompanyables(License::query())
            ->whereIn('id', $validated['order'])
            ->get()
            ->keyBy('id');

        foreach ($validated['order'] as $position => $id) {
            if ($license = $licenses->get($id)) {
                $license->sort_order = $position + 1;
                $license->save();
            }
        }

        return response()->json(['status' => 'success', 'message' => trans('killa.licenses_ux.reorder_saved')]);
    }
}
