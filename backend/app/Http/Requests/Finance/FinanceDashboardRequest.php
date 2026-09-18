<?php

namespace App\Http\Requests\Finance;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class FinanceDashboardRequest extends FormRequest
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
            'from' => ['nullable', 'date_format:Y-m-d', 'required_with:to'],
            'to' => [
                'nullable',
                'date_format:Y-m-d',
                'required_with:from',
                'after_or_equal:from',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $from = $this->input('from');

                    if (! is_string($from) || ! is_string($value) || $from === '' || $value === '') {
                        return;
                    }

                    $start = CarbonImmutable::parse($from);
                    $end = CarbonImmutable::parse($value);

                    if ($start->diffInDays($end) + 1 > 366) {
                        $fail('The selected period may not exceed 366 days.');
                    }
                },
            ],
        ];
    }
}
