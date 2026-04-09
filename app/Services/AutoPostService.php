<?php

namespace App\Services;

use App\Models\FeedPost;
use App\Models\PostAttachment;
use Illuminate\Support\Facades\Log;

class AutoPostService
{
    /**
     * Create an auto-post when user creates a new skill.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Skills $skill
     */
    public static function createSkillPost($user, $skill)
    {
        try {
            $skill->load('type', 'attachments');
            $userName = trim($user->first_name . ' ' . $user->last_name);
            $typeName = $skill->type?->name ?? 'General';
            $status = $skill->status ?? 'on-going';

            // Build post content
            $content = "@{$userName} has developed a new skill! Congrats on this awesome milestone!\n\n";

            if (!empty($skill->description)) {
                $content .= "📝 {$skill->description}\n\n";
            }

            $content .= "🏷️ Skill: {$skill->name}\n";
            $content .= "📂 Type: {$typeName}\n";
            $content .= "📌 Status: " . ucfirst($status) . "\n";

            $post = FeedPost::create([
                'user_id' => $user->id,
                'content' => $content,
                'is_published' => 1,
            ]);

            // Copy skill attachments as post attachments
            if ($skill->attachments && $skill->attachments->count() > 0) {
                foreach ($skill->attachments as $attachment) {
                    PostAttachment::create([
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                        'attachment_url' => $attachment->file_path,
                        'mime_type' => $attachment->mime_type,
                    ]);
                }
            }

            return $post;

        } catch (\Exception $e) {
            Log::error('AutoPost Skill Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create an auto-post when user creates a new academic planning.
     *
     * @param \App\Models\User $user
     * @param \App\Models\AcademicPlanning $planning
     */
    public static function createAcademicPlanningPost($user, $planning)
    {
        try {
            $planning->load('subject', 'attachments');
            $userName = trim($user->first_name . ' ' . $user->last_name);
            $subjectName = $planning->subject?->name ?? 'General';
            $status = $planning->status ?? 'on-going';

            // Build post content
            $content = "@{$userName} has created a new academic plan! Keep up the great work! 📚\n\n";

            if (!empty($planning->description)) {
                $content .= "📝 {$planning->description}\n\n";
            }

            $content .= "📖 Subject: {$subjectName}\n";
            $content .= "📌 Status: " . ucfirst($status) . "\n";

            $post = FeedPost::create([
                'user_id' => $user->id,
                'content' => $content,
                'is_published' => 1,
            ]);

            // Copy academic attachments as post attachments
            if ($planning->attachments && $planning->attachments->count() > 0) {
                foreach ($planning->attachments as $attachment) {
                    PostAttachment::create([
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                        'attachment_url' => $attachment->file_path,
                        'mime_type' => $attachment->mime_type,
                    ]);
                }
            }

            return $post;

        } catch (\Exception $e) {
            Log::error('AutoPost Academic Planning Error: ' . $e->getMessage());
            return null;
        }
    }
}
