<?php

namespace App\Console\Commands;

use App\Models\IdempotentRequest;
use Illuminate\Console\Command;

class PurgeIdempotencyKeys extends Command
{
    protected $signature = 'flights:purge-idempotency
                            {--hours=24 : Delete records older than this many hours}
                            {--force : Skip confirmation}';

    protected $description = 'Remove expired idempotency records';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $count = IdempotentRequest::where('created_at', '<', $cutoff)->count();

        if ($count === 0) {
            $this->components->info("No idempotency records older than {$hours}h.");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Delete {$count} idempotency records older than {$hours}h?")) {
            $this->components->warn('Aborted.');

            return self::SUCCESS;
        }

        $deleted = IdempotentRequest::where('created_at', '<', $cutoff)->delete();

        $this->components->info("Purged {$deleted} idempotency records.");

        return self::SUCCESS;
    }
}
