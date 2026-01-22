<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CoachingSession;

class CoachingSessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sessions = CoachingSession::with('user')->latest()->paginate(10);
        return view('admin.coaching_sessions.index', compact('sessions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.coaching_sessions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'short_description' => 'required|string',
            'long_description' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'video' => 'nullable|mimes:mp4,mov,avi|max:20000',
            'start_time' => 'required',
            'end_time' => 'required',
            'duration' => 'required|integer',
            'meeting_link' => 'required|url',
            'available_days' => 'required|array',
            'price' => 'required|numeric',
        ]);

        $data = $request->all();
        $data['user_id'] = auth()->id();

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('sessions-attachment/images'), $filename);
            $data['image'] = '/sessions-attachment/images/' . $filename;
        }

        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('sessions-attachment/videos'), $filename);
            $data['video'] = '/sessions-attachment/videos/' . $filename;
        }

        CoachingSession::create($data);

        return redirect()->route('admin.coaching-sessions.index')->with('success', 'Session created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CoachingSession $coachingSession)
    {
        return view('admin.coaching_sessions.show', compact('coachingSession'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CoachingSession $coachingSession)
    {
        return view('admin.coaching_sessions.edit', compact('coachingSession'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CoachingSession $coachingSession)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'short_description' => 'required|string',
            'long_description' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'video' => 'nullable|mimes:mp4,mov,avi|max:20000',
            'start_time' => 'required',
            'end_time' => 'required',
            'duration' => 'required|integer',
            'meeting_link' => 'required|url',
            'available_days' => 'required|array',
            'price' => 'required|numeric',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            if ($coachingSession->image && file_exists(public_path($coachingSession->image))) {
                unlink(public_path($coachingSession->image));
            }
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('sessions-attachment/images'), $filename);
            $data['image'] = '/sessions-attachment/images/' . $filename;
        }

        if ($request->hasFile('video')) {
            if ($coachingSession->video && file_exists(public_path($coachingSession->video))) {
                unlink(public_path($coachingSession->video));
            }
            $file = $request->file('video');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('sessions-attachment/videos'), $filename);
            $data['video'] = '/sessions-attachment/videos/' . $filename;
        }

        $coachingSession->update($data);

        return redirect()->route('admin.coaching-sessions.index')->with('success', 'Session updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CoachingSession $coachingSession)
    {
        if ($coachingSession->image && file_exists(public_path($coachingSession->image))) {
            unlink(public_path($coachingSession->image));
        }

        if ($coachingSession->video && file_exists(public_path($coachingSession->video))) {
            unlink(public_path($coachingSession->video));
        }

        $coachingSession->delete();
        return redirect()->route('admin.coaching-sessions.index')->with('success', 'Session deleted successfully.');
    }
}
