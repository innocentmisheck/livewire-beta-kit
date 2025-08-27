<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears the Laravel log files in storage/logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Define the log file path
        $logPath = storage_path('logs/laravel.log');

        // Check if the default laravel.log exists and clear it
        if (File::exists($logPath)) {
            File::put($logPath, ''); // Truncate the file
            $this->info('laravel.log has been cleared.');
        } else {
            $this->warn('laravel.log does not exist.');
        }

        // Optional: Clear all .log files in the logs directory (e.g., daily logs)
        $allLogs = File::glob(storage_path('logs/*.log'));
        if (count($allLogs) > 0) {
            foreach ($allLogs as $logFile) {
                File::put($logFile, ''); // Truncate each log file
                $this->info("Cleared: " . basename($logFile));
            }
            $this->info('All log files have been cleared.');
        } else {
            $this->warn('No additional log files found in storage/logs.');
        }
    }
}