{{-- [license-spend addon] shared fields for purchase create/edit.
     Expects $license and $purchase (model or empty instance). --}}
@csrf
<div class="form-group {{ $errors->has('purchase_date') ? 'has-error' : '' }}">
    <label for="purchase_date" class="col-md-3 control-label">{{ trans('general.purchase_date') }}</label>
    <div class="col-md-4">
        <input type="date" class="form-control" name="purchase_date" id="purchase_date" required
               value="{{ old('purchase_date', $purchase->purchase_date?->format('Y-m-d') ?? date('Y-m-d')) }}">
        {!! $errors->first('purchase_date', '<span class="help-block">:message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('period_start') ? 'has-error' : '' }}">
    <label for="period_start" class="col-md-3 control-label">{{ trans('killa.license_spend.period_start') }}</label>
    <div class="col-md-4">
        <input type="date" class="form-control" name="period_start" id="period_start"
               value="{{ old('period_start', $purchase->period_start?->format('Y-m-d')) }}">
        {!! $errors->first('period_start', '<span class="help-block">:message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('period_end') ? 'has-error' : '' }}">
    <label for="period_end" class="col-md-3 control-label">{{ trans('killa.license_spend.period_end') }}</label>
    <div class="col-md-4">
        <input type="date" class="form-control" name="period_end" id="period_end"
               value="{{ old('period_end', $purchase->period_end?->format('Y-m-d')) }}">
        {!! $errors->first('period_end', '<span class="help-block">:message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('term_months') ? 'has-error' : '' }}">
    <label for="term_months" class="col-md-3 control-label">{{ trans('killa.license_spend.term') }}</label>
    <div class="col-md-4">
        <select name="term_months" id="term_months" class="form-control">
            <option value="">{{ trans('killa.license_spend.term_none') }}</option>
            @foreach (\App\Models\LicensePurchase::TERM_OPTIONS as $months => $key)
                <option value="{{ $months }}" @selected((int) old('term_months', $purchase->term_months) === $months)>
                    {{ trans('killa.license_spend.terms.'.$key) }}
                </option>
            @endforeach
        </select>
        {!! $errors->first('term_months', '<span class="help-block">:message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('amount') ? 'has-error' : '' }}">
    <label for="amount" class="col-md-3 control-label">{{ trans('killa.license_spend.amount') }}</label>
    <div class="col-md-3">
        <input type="number" step="0.01" min="0" class="form-control" name="amount" id="amount"
               value="{{ old('amount', $purchase->amount) }}">
        {!! $errors->first('amount', '<span class="help-block">:message</span>') !!}
    </div>
    <div class="col-md-3">
        <select name="currency" id="currency" class="form-control" aria-label="{{ trans('killa.currency.label') }}">
            @foreach (\App\Services\Killa\CurrencyConverter::options() as $code => $label)
                <option value="{{ $code }}" @selected(old('currency', $purchase->currency ?? $license->currency ?? \App\Services\Killa\CurrencyConverter::base()) === $code)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="form-group {{ $errors->has('amount_aed') ? 'has-error' : '' }}">
    <label for="amount_aed" class="col-md-3 control-label">
        {{ trans('killa.license_spend.amount_aed', ['base' => \App\Services\Killa\CurrencyConverter::base()]) }}
    </label>
    <div class="col-md-3">
        <input type="number" step="0.01" min="0" class="form-control" name="amount_aed" id="amount_aed"
               value="{{ old('amount_aed', $purchase->amount_aed) }}">
        <p class="help-block">{{ trans('killa.license_spend.amount_aed_help', ['base' => \App\Services\Killa\CurrencyConverter::base()]) }}</p>
        {!! $errors->first('amount_aed', '<span class="help-block">:message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('ai_amount_aed') ? 'has-error' : '' }}">
    <label for="ai_amount_aed" class="col-md-3 control-label">{{ trans('killa.license_spend.ai_amount_aed') }}</label>
    <div class="col-md-3">
        <input type="number" step="0.01" min="0" class="form-control" name="ai_amount_aed" id="ai_amount_aed"
               value="{{ old('ai_amount_aed', $purchase->ai_amount_aed) }}">
        <p class="help-block">{{ trans('killa.license_spend.ai_amount_aed_help', ['base' => \App\Services\Killa\CurrencyConverter::base()]) }}</p>
        {!! $errors->first('ai_amount_aed', '<span class="help-block">:message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('invoice') ? 'has-error' : '' }}">
    <label for="invoice" class="col-md-3 control-label">{{ trans('killa.license_spend.invoice') }}</label>
    <div class="col-md-5">
        <input type="file" name="invoice" id="invoice" class="form-control">
        @if ($purchase->hasInvoice())
            <p class="help-block">
                <a href="{{ route('licenses.purchases.invoice', [$license->id, $purchase->id]) }}">{{ trans('killa.license_spend.invoice_download') }}</a>
                &nbsp;
                <label style="font-weight: normal;">
                    <input type="checkbox" name="remove_invoice" value="1"> {{ trans('killa.license_spend.invoice_remove') }}
                </label>
            </p>
        @endif
        {!! $errors->first('invoice', '<span class="help-block">:message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('notes') ? 'has-error' : '' }}">
    <label for="notes" class="col-md-3 control-label">{{ trans('general.notes') }}</label>
    <div class="col-md-7">
        <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $purchase->notes) }}</textarea>
        {!! $errors->first('notes', '<span class="help-block">:message</span>') !!}
    </div>
</div>
