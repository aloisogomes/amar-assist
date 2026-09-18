<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class FinanceImportProgress implements ShouldBroadcastNow
{
    public function __construct(
        public int $userId,
        public string $importId,
        public int $processed,
        public int $total,
        public int $created,
        public int $failed,
        public int $percent,
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
        return 'finance.import.progress';
    }

    /**
     * @return array{import_id: string, processed: int, total: int, created: int, failed: int, percent: int}
     */
    public function broadcastWith(): array
    {
        return [
            'import_id' => $this->importId,
            'processed' => $this->processed,
            'total' => $this->total,
            'created' => $this->created,
            'failed' => $this->failed,
            'percent' => $this->percent,
        ];
    }
}
