<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;


class GoalController extends Controller
{
       // List all goals of the logged-in user
    public function index(Request $request)
    {
        $query = Goal::where('user_id', auth()->id());

        // Optional filters
        if ($request->filled('type')) {
            $query->where('type', $request->type); // short or long
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status); // not_initiated, in_progress, complete
        }

        if ($request->filled('completion_date')) {
            $query->whereDate('completion_date', $request->completion_date);
        }

        $goals = $query->latest()->paginate(10);

        return response()->json([
            'message' => '',
            'status' => true,
            'data' => $goals
        ]);
    }

    // Create a goal
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'goal' => 'required|string|max:255',
            'type' => ['required', Rule::in(['short', 'long'])],
            'completion_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->type === 'short') {
                $maxDate = now()->addMonths(6);
                if ($request->completion_date > $maxDate) {
                    $validator->errors()->add('completion_date', 'Short goals must be completed within 6 months.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        $goal = Goal::create([
            'user_id' => $request->user()->id,
            'goal' => $request->goal,
            'type' => $request->type,
            'completion_date' => $request->completion_date,
            'description' => $request->description,
            'status' => 'not_initiated',
        ]);

        return response()->json(['message' => 'Goal created', 'status' => true, 'data' => $goal]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
        // $request->validate([
            'goal' => 'required|string|max:255',
            'type' => ['required', Rule::in(['short', 'long'])],
            'completion_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $validator->after(function ($validator) use ($request) {
            if ($request->type === 'short') {
                $maxDate = now()->addMonths(6);
                if ($request->completion_date > $maxDate) {
                    $validator->errors()->add('completion_date', 'Short goals must be completed within 6 months.');
                }
            }
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        $goal = Goal::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        $goal->update([
            'goal' => $request->goal,
            'type' => $request->type,
            'completion_date' => $request->completion_date,
            'description' => $request->description,
        ]);

        return response()->json(['message' => 'Goal updated successfully.', 'status' => true, 'data' => $goal]);
    }

    // Update status
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['in_progress', 'completed', 'not_initiated'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        $goal = Goal::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        $goal->status = $request->status;
        $goal->save();

        return response()->json(['message' => 'Goal status updated', 'status' => true, 'data' => $goal]);
    }

    // Delete goal
    public function destroy(Request $request, $id)
    {
        $goal = Goal::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        $goal->delete();

        return response()->json(['message' => 'Goal deleted.', 'status' => true]);
    }
}
