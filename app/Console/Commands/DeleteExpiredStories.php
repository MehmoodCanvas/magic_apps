<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Story;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class DeleteExpiredStories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stories:delete-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes stories that are older than 24 hours along with their files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredStories = Story::with('attachments')->where('expires_at', '<=', Carbon::now())->get();

        $count = 0;
        foreach ($expiredStories as $story) {
            foreach ($story->attachments as $attachment) {
                $filePath = public_path($attachment->file_path);
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }
            $story->delete();
            $count++;
        }

        $this->info("Successfully deleted {$count} expired stories.");
    }
}
