<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CoachingSession;
use App\Models\SessionBooking;
use Illuminate\Support\Facades\Storage;

class CoachingSessionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = CoachingSession::with('user')->where('status', 'active');

            if ($request->has('type')) {
                if ($request->type == 'admin') {
                    $query->whereHas('user', function($q) {
                        $q->where('user_role', 'admin');
                    });
                } elseif ($request->type == 'user') {
                    $query->whereHas('user', function($q) {
                        $q->where('user_role', 'user');
                    });
                }
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%")
                      ->orWhere('short_description', 'LIKE', "%$search%")
                      ->orWhere('long_description', 'LIKE', "%$search%")
                      ->orWhereHas('user', function($uq) use ($search) {
                          $uq->where('first_name', 'LIKE', "%$search%")
                             ->orWhere('last_name', 'LIKE', "%$search%")
                             ->orWhere('email', 'LIKE', "%$search%");
                      });
                });
            }

            $sessions = $query->latest()->paginate(20);

            return response()->json([
                'status' => true,
                'data' => $sessions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id){
        try {
            $session = CoachingSession::with('user')->findOrFail($id);
            return response()->json([
                'status' => true,
                'data' => $session
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    
    public function mySessions()
    {
        try {
            $sessions = CoachingSession::with('user')->where('user_id', auth()->id())->latest()->paginate(20);

            return response()->json([
                'status' => true,
                'data' => $sessions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            if ($user->user_role !== 'admin' && !$user->can_manage_sessions) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to create sessions.'
                ], 403);
            }
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'short_description' => 'required|string',
                'long_description' => 'nullable|string',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'video' => 'nullable|file|mimes:mp4,mov,avi,wmv|max:20480',
                'start_time' => 'required',
                'end_time' => 'required',
                'duration' => 'required|integer',
                'meeting_link' => 'required|url',
                'available_days' => 'required|array',
                'price' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error.',
                    'errors' => $validator->errors()
                ], 422);
            }

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

            $session = CoachingSession::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Session created successfully.',
                'data' => $session
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSlots(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $session = CoachingSession::findOrFail($id);
            $date = $request->date;
            $dayOfWeek = date('l', strtotime($date));

            if (!in_array($dayOfWeek, $session->available_days)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Session is not available on this day.'
                ], 422);
            }

            $startTime = strtotime($session->start_time);
            $endTime = strtotime($session->end_time);
            $duration = $session->duration * 60; // to seconds

            $slots = [];
            $current = $startTime;

            // Get existing bookings for this day
            $bookings = SessionBooking::where('coaching_session_id', $id)
                ->where('booking_date', $date)
                ->where('status', 'booked')
                ->get();

            while ($current + $duration <= $endTime) {
                $slotStart = date('H:i:s', $current);
                $slotEnd = date('H:i:s', $current + $duration);

                $isBooked = $bookings->contains(function ($booking) use ($slotStart) {
                    return $booking->start_time == $slotStart;
                });

                $slots[] = [
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                    'status' => $isBooked ? 'booked' : 'available'
                ];

                $current += $duration;
            }

            return response()->json([
                'status' => true,
                'data' => $slots
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            if ($user->user_role !== 'admin' && !$user->can_manage_sessions) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to manage sessions.'
                ], 403);
            }

            $session = CoachingSession::findOrFail($id);

            if ($session->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'short_description' => 'required|string',
                'long_description' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'video' => 'nullable|file|mimes:mp4,mov,avi,wmv|max:20480',
                'start_time' => 'required',
                'end_time' => 'required',
                'duration' => 'required|integer',
                'meeting_link' => 'required|url',
                'available_days' => 'required|array',
                'price' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();

            if ($request->hasFile('image')) {
                if ($session->image && file_exists(public_path($session->image))) {
                    unlink(public_path($session->image));
                }
                $file = $request->file('image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('sessions-attachment/images'), $filename);
                $data['image'] = '/sessions-attachment/images/' . $filename;
            }

            if ($request->hasFile('video')) {
                if ($session->video && file_exists(public_path($session->video))) {
                    unlink(public_path($session->video));
                }
                $file = $request->file('video');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('sessions-attachment/videos'), $filename);
                $data['video'] = '/sessions-attachment/videos/' . $filename;
            }

            $session->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Session updated successfully.',
                'data' => $session
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = auth()->user();
            if ($user->user_role !== 'admin' && !$user->can_manage_sessions) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have permission to manage sessions.'
                ], 403);
            }

            $session = CoachingSession::findOrFail($id);

            if ($session->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized.'
                ], 403);
            }

            if ($session->image && file_exists(public_path($session->image))) {
                unlink(public_path($session->image));
            }

            if ($session->video && file_exists(public_path($session->video))) {
                unlink(public_path($session->video));
            }

            $session->delete();

            return response()->json([
                'status' => true,
                'message' => 'Session deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }
}
