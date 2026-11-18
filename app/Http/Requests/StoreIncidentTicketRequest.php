<?php

namespace App\Http\Requests;

use App\Models\IncidentTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requester_name' => ['nullable', 'string', 'max:191'],
            'category' => ['required', 'string', 'max:191'],
            'task_type' => ['required', 'string', 'max:191'],
            'issue' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(IncidentTicket::STATUSES)],
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
        ];
    }
}
