<?php

namespace App\Http\Requests\Finance;

use App\Enums\FinanceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListFinancesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::enum(FinanceType::class)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'min_amount' => ['nullable', 'integer', 'min:0'],
            'max_amount' => ['nullable', 'integer', 'min:0', 'gte:min_amount'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
