<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AgentSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'synced_at' => ['required', 'date'],
            'accounts' => ['required', 'array'],
            'accounts.*.qb_list_id' => ['required', 'string', 'max:64'],
            'accounts.*.full_name' => ['required', 'string', 'max:255'],
            'accounts.*.account_type' => ['required', 'string', 'max:64'],
            'accounts.*.is_active' => ['required', 'boolean'],
            'accounts.*.balance' => ['required', 'numeric'],
            'accounts.*.total_balance' => ['required', 'numeric'],
            'customers' => ['required', 'array'],
            'customers.*.qb_list_id' => ['required', 'string', 'max:64'],
            'customers.*.full_name' => ['required', 'string', 'max:255'],
            'customers.*.is_active' => ['required', 'boolean'],
            'customers.*.balance' => ['required', 'numeric'],
            'customers.*.total_balance' => ['required', 'numeric'],
            'customers.*.sales_rep_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
