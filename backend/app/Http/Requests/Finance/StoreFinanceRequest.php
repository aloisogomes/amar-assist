<?php

namespace App\Http\Requests\Finance;

use App\Enums\FinanceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinanceRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0'],
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'type' => ['required', Rule::enum(FinanceType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'description' => 'descrição',
            'amount' => 'valor',
            'date' => 'data',
            'type' => 'tipo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.required' => 'Informe a descrição.',
            'description.max' => 'A descrição deve ter no máximo 255 caracteres.',
            'amount.required' => 'Informe o valor.',
            'amount.integer' => 'Informe um valor válido.',
            'amount.min' => 'O valor deve ser maior ou igual a zero.',
            'date.required' => 'Informe a data.',
            'date.date' => 'Informe uma data válida.',
            'date.date_format' => 'Informe uma data válida.',
            'type.required' => 'Selecione o tipo.',
            'type.enum' => 'Selecione o tipo.',
        ];
    }
}
