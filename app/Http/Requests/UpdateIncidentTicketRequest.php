<?php

namespace App\Http\Requests;

use App\Models\IncidentTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requester_name' => ['sometimes', 'nullable', 'string', 'max:191'],
            'category' => ['sometimes', 'nullable', 'string', 'max:191'],
            'task_type' => ['sometimes', 'nullable', 'string', 'max:191'],
            'issue' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'details' => ['sometimes', 'required', 'string', 'max:5000'],
            'status' => ['sometimes', Rule::in(IncidentTicket::STATUSES)],
            'asset_id' => ['sometimes', 'nullable', 'integer', 'exists:assets,id'],
        ];
    }
}
