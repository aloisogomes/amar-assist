<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class FinanceDashboardCache
{
    public const string VERSION_KEY = 'finances.dashboard.version';

    public const int TTL_SECONDS = 600;

    /**
     * @param  callable(): array<string, mixed>  $callback
     * @return array<string, mixed>
     */
    public function remember(string $from, string $to, callable $callback): array
    {
        /** @var array<string, mixed> $payload */
        $payload = Cache::remember($this->key($from, $to), self::TTL_SECONDS, $callback);

        return $payload;
    }

    public function bump(): void
    {
        $version = (int) Cache::get(self::VERSION_KEY, 0);

        Cache::forever(self::VERSION_KEY, $version + 1);
    }

    public function key(string $from, string $to): string
    {
        $version = (int) Cache::get(self::VERSION_KEY, 0);

        return "finances.dashboard.v{$version}.{$from}.{$to}";
    }
}
