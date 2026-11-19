<?php

namespace App\Http\Requests;

class StoreLicensePurchaseRequest extends Request
{
    protected $rules = [
        'purchase_date' => ['required', 'date_format:Y-m-d'],
        'period_start' => ['nullable', 'date_format:Y-m-d'],
        'period_end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:period_start'],
        'term_months' => ['nullable', 'integer', 'min:1', 'max:120'],
        'amount' => ['nullable', 'numeric', 'gte:0'],
        'currency' => ['nullable', 'string', 'size:3'],
        'amount_aed' => ['nullable', 'numeric', 'gte:0'],
        'ai_amount_aed' => ['nullable', 'numeric', 'gte:0', 'lte:amount_aed'],
        'invoice' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,gif,doc,docx,xls,xlsx,csv,txt,zip', 'max:20480'],
        'notes' => ['nullable', 'string'],
    ];
}
