<?php

namespace App\Models;

use App\Enums\FinanceType;
use Database\Factories\FinanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['description', 'amount', 'date', 'type'])]
class Finance extends Model
{
    /** @use HasFactory<FinanceFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Finance $finance): void {
            $finance->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'date' => 'date',
            'type' => FinanceType::class,
        ];
    }
}
