<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class FinanceImportCompleted implements ShouldBroadcastNow
{
    /**
     * @param  list<array{row: int, messages: list<string>}>  $errors
     */
    public function __construct(
        public int $userId,
        public string $importId,
        public int $created,
        public int $failed,
        public array $errors,
        public int $percent = 100,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'finance.import.completed';
    }

    /**
     * @return array{import_id: string, created: int, failed: int, errors: list<array{row: int, messages: list<string>}>, percent: int}
     */
    public function broadcastWith(): array
    {
        return [
            'import_id' => $this->importId,
            'created' => $this->created,
            'failed' => $this->failed,
            'errors' => $this->errors,
            'percent' => $this->percent,
        ];
    }
}
