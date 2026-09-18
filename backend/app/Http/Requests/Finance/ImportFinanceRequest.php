<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class ImportFinanceRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:xlsx,csv,txt',
                'extensions:xlsx,csv',
            ],
        ];
    }
}
