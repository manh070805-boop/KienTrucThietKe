<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $action = $this->route('action') ?? ($this->isMethod('POST') ? 'create' : 'update');
        $version = ['version' => ['required', 'integer', 'min:1']];
        $draft = ['type' => ['sometimes', Rule::in(['FEEDBACK', 'PROPOSAL'])], 'title' => ['nullable', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:10000'], 'category_id' => ['nullable', 'integer', 'exists:categories,id']];
        $body = ['body' => ['required', 'string', 'max:10000']];
        $reason = ['reason' => ['required', 'string', 'max:500']];
        $assignment = ['department_id' => ['required', 'integer', 'exists:departments,id'], 'staff_id' => ['required', 'integer', 'exists:users,id']];

        return match ($action) {
            'create' => ['client_request_id' => ['required', 'uuid'], 'type' => ['required', Rule::in(['FEEDBACK', 'PROPOSAL'])]],
            'update' => $version + $draft,
            'submit' => $version + ['title' => ['prohibited'], 'description' => ['prohibited'], 'category_id' => ['prohibited']],
            'assign' => $version + $assignment + ['reason' => ['nullable', 'string', 'max:500']],
            'transfer' => $version + $assignment + $reason,
            'classify' => $version + ['category_id' => ['required', 'integer', 'exists:categories,id']],
            'request-information', 'resolve', 'internal-note' => $version + $body,
            'messages' => $version + $body + ['kind' => ['prohibited'], 'visibility' => ['prohibited']],
            'reject', 'reopen' => $version + $reason,
            'rate' => $version + ['score' => ['required', 'integer', 'between:1,5'], 'comment' => ['nullable', 'string', 'max:1000']],
            default => $version,
        };
    }
}
