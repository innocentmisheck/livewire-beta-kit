<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears all Laravel caches automatically';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Clear application cache
        Artisan::call('cache:clear');
        $this->info('Application cache cleared.');

        // Clear configuration cache
        Artisan::call('config:clear');
        $this->info('Configuration cache cleared.');

        // Clear route cache
        Artisan::call('route:clear');
        $this->info('Route cache cleared.');

        // Clear view cache
        Artisan::call('view:clear');
        $this->info('View cache cleared.');

        // Clear compiled class cache (optional)
        Artisan::call('clear-compiled');
        $this->info('Compiled classes cleared.');

        // Optional: Log the action
        \Log::info('Caches cleared automatically at ' . now());
    }
}