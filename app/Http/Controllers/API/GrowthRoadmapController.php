<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Goal;
use App\Models\Skills;
use App\Models\AcademicPlanning;
use App\Models\AcademicPlanningAttachment;
use App\Models\SessionBooking;
use App\Models\Idea;
use App\Models\CoachingSession;

class GrowthRoadmapController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        // Level 1 Tasks
        $l1_t1 = $profile && $profile->gender && $profile->country_id && $profile->born_date && $profile->employment_status_id;
        $l1_t2 = $profile && $profile->category_id;
        $l1_t3 = $profile && $profile->qualification_id;
        $l1_t4 = AcademicPlanningAttachment::where('user_id', $user->id)->exists();
        $l1_t5 = $profile && $profile->preferred_work_style_id;
        $l1_t6 = Goal::where('user_id', $user->id)->exists();
        
        $level1Tasks = [
            ['title' => 'Complete profile information', 'is_completed' => (bool) $l1_t1],
            ['title' => 'Specify Interest', 'is_completed' => (bool) $l1_t2],
            ['title' => 'Provide Education details', 'is_completed' => (bool) $l1_t3],
            ['title' => 'Upload Academic Certificate', 'is_completed' => (bool) $l1_t4],
            ['title' => 'Define Work Style', 'is_completed' => (bool) $l1_t5],
            ['title' => 'Set Aspirations', 'is_completed' => (bool) $l1_t6],
        ];

        // Level 2 Tasks
        $l2_t1 = Skills::where('user_id', $user->id)->exists();
        $l2_t2 = AcademicPlanning::where('user_id', $user->id)->exists();
        $l2_t3 = SessionBooking::where('user_id', $user->id)->exists();
        
        $level2Tasks = [
            ['title' => 'Participate in Skill Development (70-20-10 Model)', 'is_completed' => (bool) $l2_t1],
            ['title' => 'Engage in Academic Planning', 'is_completed' => (bool) $l2_t2],
            ['title' => 'Attend Coaching Sessions', 'is_completed' => (bool) $l2_t3],
        ];

        // Level 3 Tasks
        $l3_t1 = Idea::where('user_id', $user->id)->exists();
        
        $level3Tasks = [
            ['title' => 'Submit and publish an Idea of the Month', 'is_completed' => (bool) $l3_t1],
        ];

        // Level 4 Tasks
        $l4_t1 = $profile && ($profile->bio || $profile->resume);

        $level4Tasks = [
            ['title' => 'Create a Professional Bio/Resume', 'is_completed' => (bool) $l4_t1],
        ];

        // Level 5 Tasks
        $l5_t1 = CoachingSession::where('user_id', $user->id)->exists();

        $level5Tasks = [
            ['title' => 'Offer Coaching Sessions to other users', 'is_completed' => (bool) $l5_t1],
        ];

        // Calculate Percentages
        $level1Percentage = $this->calculatePercentage($level1Tasks);
        $level2Percentage = $this->calculatePercentage($level2Tasks);
        $level3Percentage = $this->calculatePercentage($level3Tasks);
        $level4Percentage = $this->calculatePercentage($level4Tasks);
        $level5Percentage = $this->calculatePercentage($level5Tasks);

        // Determine if levels are locked (a level is locked if the previous level is not 100% complete)
        $roadmap = [
            [
                'level' => 1,
                'title' => 'Initial',
                'percentage' => $level1Percentage,
                'is_locked' => false,
                'tasks' => $level1Tasks,
            ],
            [
                'level' => 2,
                'title' => 'Developing',
                'percentage' => $level2Percentage,
                'is_locked' => $level1Percentage < 100,
                'tasks' => $level2Tasks,
            ],
            [
                'level' => 3,
                'title' => 'Established',
                'percentage' => $level3Percentage,
                'is_locked' => $level2Percentage < 100 || $level1Percentage < 100,
                'tasks' => $level3Tasks,
            ],
            [
                'level' => 4,
                'title' => 'Advance',
                'percentage' => $level4Percentage,
                'is_locked' => $level3Percentage < 100 || $level2Percentage < 100 || $level1Percentage < 100,
                'tasks' => $level4Tasks,
            ],
            [
                'level' => 5,
                'title' => 'Leading',
                'percentage' => $level5Percentage,
                'is_locked' => $level4Percentage < 100 || $level3Percentage < 100 || $level2Percentage < 100 || $level1Percentage < 100,
                'tasks' => $level5Tasks,
            ],
        ];

        return response()->json([
            'status' => true,
            'message' => 'Growth roadmap fetched successfully.',
            'data' => $roadmap
        ]);
    }

    private function calculatePercentage($tasks)
    {
        $total = count($tasks);
        $completed = 0;
        foreach ($tasks as $task) {
            if ($task['is_completed']) {
                $completed++;
            }
        }
        
        return $total > 0 ? round(($completed / $total) * 100) : 0;
    }
}
