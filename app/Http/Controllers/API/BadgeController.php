<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\User;
use App\Models\Goal;
use App\Models\Skills;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BadgeController extends Controller
{
    // ======================================
    // ADMIN METHODS
    // ======================================

    public function indexAdmin()
    {
        $badges = Badge::latest()->get();
        return response()->json(['status' => true, 'data' => $badges]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'type' => ['required', Rule::in(['skills', 'goals'])],
            'required_amount' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        $data = $request->only(['name', 'description', 'type', 'required_amount']);

        if ($request->hasFile('icon')) {
            $file = $request->file('icon');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('badges-attachment'), $filename);
            $data['icon'] = '/badges-attachment/' . $filename;
        }

        $badge = Badge::create($data);

        return response()->json(['status' => true, 'message' => 'Badge Created Successfully', 'data' => $badge]);
    }

    public function update(Request $request, $id)
    {
        $badge = Badge::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'type' => ['required', Rule::in(['skills', 'goals'])],
            'required_amount' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        $data = $request->only(['name', 'description', 'type', 'required_amount']);

        if ($request->hasFile('icon')) {
            // Delete old icon if it exists
            if ($badge->icon) {
                $oldIconPath = public_path($badge->icon);
                if (File::exists($oldIconPath)) {
                    File::delete($oldIconPath);
                }
            }

            $file = $request->file('icon');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('badges-attachment'), $filename);
            $data['icon'] = '/badges-attachment/' . $filename;
        }

        $badge->update($data);

        return response()->json(['status' => true, 'message' => 'Badge Updated Successfully', 'data' => $badge]);
    }

    public function destroy($id)
    {
        $badge = Badge::findOrFail($id);

        if ($badge->icon) {
            $oldIconPath = public_path($badge->icon);
            if (File::exists($oldIconPath)) {
                File::delete($oldIconPath);
            }
        }

        $badge->delete();

        return response()->json(['status' => true, 'message' => 'Badge Deleted Successfully']);
    }

    // ======================================
    // USER METHODS
    // ======================================

    public function userBadges(Request $request)
    {   
        if ($request->user_id) {
            $user = User::findOrFail($request->user_id);
        } else {
            $user = $request->user();
        }
        
        $query = Badge::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $badges = $query->orderBy('required_amount', 'asc')->get();
        
        // Calculate user totals
        $completedSkillsCount = Skills::where('user_id', $user->id)
                                      ->where('status', 'completed')
                                      ->count();
                                      
        $completedGoalsCount = Goal::where('user_id', $user->id)
                                   ->where('status', 'completed')
                                   ->count();

        // Attach progress to each badge
        $badgesWithProgress = $badges->map(function ($badge) use ($completedSkillsCount, $completedGoalsCount) {
            $currentCount = 0;

            if ($badge->type === 'skills') {
                $currentCount = $completedSkillsCount;
            } elseif ($badge->type === 'goals') {
                $currentCount = $completedGoalsCount;
            }

            // Cap the progress at the required amount
            $progressCount = min($currentCount, $badge->required_amount);
            $isAchieved = $currentCount >= $badge->required_amount;

            $badge->progress = [
                'current' => $progressCount,
                'required' => $badge->required_amount,
                'is_achieved' => $isAchieved,
            ];

            return $badge;
        });

        return response()->json(['status' => true, 'data' => $badgesWithProgress]);
    }

    /**
     * Combined Achievements Summary for the current user.
     * Returns: Academic Trophies count, current Skills badge, current Goals badge.
     */
    public function userAchievements(Request $request)
    {
        if ($request->user_id) {
            $user = User::findOrFail($request->user_id);
        } else {
            $user = $request->user();
        }

        // 1. Academic Trophies
        $trophyCount = \App\Models\AcademicPlanning::where('user_id', $user->id)->where('has_trophy', true)->count();

        // 2. Skills — find the highest achieved badge, fallback to smallest
        $completedSkillsCount = Skills::where('user_id', $user->id)->where('status', 'completed')->count();

        $currentSkillsBadge = Badge::where('type', 'skills')->where('required_amount', '<=', $completedSkillsCount)->orderBy('required_amount', 'desc')->first();

        // If no badge earned, show the smallest one as the next target
        if (!$currentSkillsBadge) {
            $currentSkillsBadge = Badge::where('type', 'skills')->orderBy('required_amount', 'asc')->first();
        }

        // 3. Goals — find the highest achieved badge, fallback to smallest
        $completedGoalsCount = Goal::where('user_id', $user->id)->where('status', 'completed')->count();

        $currentGoalsBadge = Badge::where('type', 'goals')->where('required_amount', '<=', $completedGoalsCount)->orderBy('required_amount', 'desc')->first();

        if (!$currentGoalsBadge) {
            $currentGoalsBadge = Badge::where('type', 'goals')->orderBy('required_amount', 'asc')->first();
        }

        return response()->json([
            'status' => true,
            'data' => [
                'academic_trophies' => [
                    'title' => 'Academic Trophies',
                    'icon' => '/badges-attachment/trophy-icon.png',
                    'count' => $trophyCount,
                ],
                'skills' => [
                    'title' => 'Skills Badges',
                    'icon' => $currentSkillsBadge ? $currentSkillsBadge->icon : 0,
                    'required_count' => $currentSkillsBadge ? $currentSkillsBadge->required_amount : 0,
                    'count' => $completedSkillsCount,
                ],
                'goals' => [
                    'title' => 'Goals Badges',
                    'icon' => $currentGoalsBadge ? $currentGoalsBadge->icon : 0,
                    'required_count' => $currentGoalsBadge ? $currentGoalsBadge->required_amount : 0,
                    'count' => $completedGoalsCount,
                ],
            ],
        ]);
    }
}
