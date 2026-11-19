<?php

namespace App\Http\Controllers\Killa;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLicensePurchaseRequest;
use App\Http\Requests\UpdateLicensePurchaseRequest;
use App\Models\License;
use App\Models\LicensePurchase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [license-spend addon] CRUD for license purchases/renewals. Authorization
 * rides on the License policy: viewing/downloading needs `view`, mutations
 * need `update` on the parent license.
 */
class LicensePurchasesController extends Controller
{
    public function create(License $license): View
    {
        $this->authorize('update', $license);

        return view('killa/licenses/purchases/create', [
            'license' => $license,
            'purchase' => new LicensePurchase(['purchase_date' => date('Y-m-d')]),
        ]);
    }

    public function store(StoreLicensePurchaseRequest $request, License $license): RedirectResponse
    {
        $this->authorize('update', $license);

        $purchase = new LicensePurchase;
        $purchase->license_id = $license->id;
        $purchase->created_by = auth()->id();
        $purchase->fill($this->sanitized($request));

        if ($request->hasFile('invoice')) {
            $purchase->invoice_file = $this->storeInvoice($request, $license);
        }

        if (! $purchase->save()) {
            return redirect()->back()->withInput()->withErrors($purchase->getErrors());
        }

        return redirect()->route('licenses.show', $license->id)
            ->with('success', trans('killa.license_spend.purchase_saved'));
    }

    public function edit(License $license, LicensePurchase $purchase): View
    {
        $this->authorize('update', $license);
        abort_if($purchase->license_id !== $license->id, 404);

        return view('killa/licenses/purchases/edit', [
            'license' => $license,
            'purchase' => $purchase,
        ]);
    }

    public function update(UpdateLicensePurchaseRequest $request, License $license, LicensePurchase $purchase): RedirectResponse
    {
        $this->authorize('update', $license);
        abort_if($purchase->license_id !== $license->id, 404);

        $purchase->fill($this->sanitized($request));

        if ($request->boolean('remove_invoice')) {
            $this->deleteInvoice($purchase);
            $purchase->invoice_file = null;
        }

        if ($request->hasFile('invoice')) {
            $this->deleteInvoice($purchase);
            $purchase->invoice_file = $this->storeInvoice($request, $license);
        }

        if (! $purchase->save()) {
            return redirect()->back()->withInput()->withErrors($purchase->getErrors());
        }

        return redirect()->route('licenses.show', $license->id)
            ->with('success', trans('killa.license_spend.purchase_saved'));
    }

    public function destroy(License $license, LicensePurchase $purchase): RedirectResponse
    {
        $this->authorize('update', $license);
        abort_if($purchase->license_id !== $license->id, 404);

        $this->deleteInvoice($purchase);
        $purchase->delete();

        return redirect()->route('licenses.show', $license->id)
            ->with('success', trans('killa.license_spend.purchase_deleted'));
    }

    public function invoice(License $license, LicensePurchase $purchase): StreamedResponse
    {
        $this->authorize('view', $license);
        abort_if($purchase->license_id !== $license->id, 404);
        abort_if(! $purchase->invoice_file || ! Storage::disk('local')->exists($purchase->invoice_file), 404);

        return Storage::disk('local')->download($purchase->invoice_file);
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitized(StoreLicensePurchaseRequest|UpdateLicensePurchaseRequest $request): array
    {
        return array_map(
            fn ($value) => $value === '' ? null : $value,
            $request->only([
                'purchase_date', 'period_start', 'period_end', 'term_months',
                'amount', 'currency', 'amount_aed', 'ai_amount_aed', 'notes',
            ])
        );
    }

    private function storeInvoice(StoreLicensePurchaseRequest|UpdateLicensePurchaseRequest $request, License $license): string
    {
        return $request->file('invoice')->store(
            'private_uploads/licenses/'.$license->id.'/purchases',
            'local'
        );
    }

    private function deleteInvoice(LicensePurchase $purchase): void
    {
        if ($purchase->invoice_file && Storage::disk('local')->exists($purchase->invoice_file)) {
            Storage::disk('local')->delete($purchase->invoice_file);
        }
    }
}
