<?php

namespace App\Http\Resources;

use App\Enums\FinanceType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'description' => $this->description,
            'amount' => $this->amount,
            'date' => $this->date?->toDateString(),
            'type' => $this->type instanceof FinanceType ? $this->type->value : $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
