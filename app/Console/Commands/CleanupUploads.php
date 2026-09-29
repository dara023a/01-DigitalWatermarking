<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WatermarkStorageService;
use Illuminate\Console\Command;

class CleanupUploads extends Command
{
    protected $signature = 'uploads:cleanup {--hours=24 : Batas usia file dalam jam}';

    protected $description = 'Bersihkan file upload user yang sudah lama';

    public function handle(WatermarkStorageService $storage): int
    {
        $hours = (int) $this->option('hours');
        $deleted = $storage->cleanupOldUploads($hours);

        $this->info("{$deleted} file upload dihapus (lebih dari {$hours} jam).");

        return self::SUCCESS;
    }
}
