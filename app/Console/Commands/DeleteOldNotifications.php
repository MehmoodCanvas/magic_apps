<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeleteOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete notifications older than 7 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $deleted = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('created_at', '<', now()->subDays(7))
            ->delete();

        $this->info("Deleted {$deleted} old notifications.");
    }
}
