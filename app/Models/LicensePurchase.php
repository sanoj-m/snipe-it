<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Watson\Validating\ValidatingTrait;

/**
 * [license-spend addon] One purchase/renewal event for a license, tracked in
 * its original currency plus the amount actually paid in AED (and optionally
 * the bundled AI portion of that AED amount).
 */
class LicensePurchase extends Model
{
    use SoftDeletes;
    use ValidatingTrait;

    public const TERM_OPTIONS = [
        1 => 'monthly',
        3 => 'quarterly',
        6 => 'half-yearly',
        12 => 'yearly',
        24 => '2-year',
        36 => '3-year',
    ];

    protected $table = 'license_purchases';

    protected $fillable = [
        'license_id',
        'purchase_date',
        'period_start',
        'period_end',
        'term_months',
        'amount',
        'currency',
        'amount_aed',
        'ai_amount_aed',
        'invoice_file',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'license_id' => 'integer',
        'term_months' => 'integer',
        'amount' => 'float',
        'amount_aed' => 'float',
        'ai_amount_aed' => 'float',
        'created_by' => 'integer',
    ];

    protected $rules = [
        'license_id' => 'required|integer|exists:licenses,id',
        'purchase_date' => 'required|date_format:Y-m-d',
        'period_start' => 'nullable|date_format:Y-m-d',
        'period_end' => 'nullable|date_format:Y-m-d|after_or_equal:period_start',
        'term_months' => 'nullable|integer|min:1|max:120',
        'amount' => 'nullable|numeric|gte:0',
        'currency' => 'nullable|string|size:3',
        'amount_aed' => 'nullable|numeric|gte:0',
        'ai_amount_aed' => 'nullable|numeric|gte:0|lte:amount_aed',
        'notes' => 'nullable|string',
    ];

    /**
     * Date setters store the raw Y-m-d value. Eloquent's default setAttribute
     * would convert date-castable values to the datetime format
     * (Y-m-d H:i:s), which then fails this model's date_format:Y-m-d
     * validation rules (same pattern as SnipeModel::setPurchaseDateAttribute).
     */
    public function setPurchaseDateAttribute($value)
    {
        $this->attributes['purchase_date'] = ($value == '') ? null : $value;
    }

    public function setPeriodStartAttribute($value)
    {
        $this->attributes['period_start'] = ($value == '') ? null : $value;
    }

    public function setPeriodEndAttribute($value)
    {
        $this->attributes['period_end'] = ($value == '') ? null : $value;
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Coverage length in months for amortization: the explicit period if both
     * bounds exist, else the declared term, else 1 (cash in purchase month).
     */
    public function coverageMonths(): int
    {
        if ($this->period_start && $this->period_end) {
            return max(1, (int) $this->period_start->diffInMonths($this->period_end));
        }

        return max(1, (int) ($this->term_months ?? 1));
    }

    public function hasInvoice(): bool
    {
        return (bool) $this->invoice_file;
    }
}
