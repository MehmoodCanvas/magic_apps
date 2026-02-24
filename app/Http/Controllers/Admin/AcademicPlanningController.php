<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicPlanning;
use App\Models\User;
use Illuminate\Http\Request;

class AcademicPlanningController extends Controller
{
    /**
     * Display all academic plannings with optional user filter.
     */
    public function index(Request $request)
    {
        $query = AcademicPlanning::with(['user', 'subject', 'attachments']);

        // Filter by specific user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $plannings = $query->latest()->paginate(20);
        $users = User::select('id', 'first_name', 'last_name')->orderBy('first_name')->get();

        return view('admin.academic_plannings.index', compact('plannings', 'users'));
    }

    /**
     * Toggle trophy status for a specific academic planning.
     */
    public function toggleTrophy($id)
    {
        $planning = AcademicPlanning::findOrFail($id);
        $planning->has_trophy = !$planning->has_trophy;
        $planning->save();

        $status = $planning->has_trophy ? 'awarded' : 'removed';

        return redirect()->back()->with('success', "Trophy {$status} for planning #{$planning->id}.");
    }
}
