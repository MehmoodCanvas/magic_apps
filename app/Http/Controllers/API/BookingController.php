<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CoachingSession;
use App\Models\SessionBooking;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'items' => 'required|array',
                'items.*.session_id' => 'required|exists:coaching_sessions,id',
                'items.*.booking_date' => 'required|date',
                'items.*.start_time' => 'required',
                'items.*.end_time' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check availability for all items first
            foreach ($request->items as $item) {
                $alreadyBooked = SessionBooking::where('coaching_session_id', $item['session_id'])
                    ->where('booking_date', $item['booking_date'])
                    ->where('start_time', $item['start_time'])
                    ->where('status', 'booked')
                    ->exists();

                if ($alreadyBooked) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Slot already booked for session ID ' . $item['session_id'] . ' at ' . $item['start_time']
                    ], 422);
                }
            }

            $bookings = [];
            foreach ($request->items as $item) {
                $session = CoachingSession::findOrFail($item['session_id']);
                
                $bookings[] = SessionBooking::create([
                    'user_id' => auth()->id(),
                    'coaching_session_id' => $session->id,
                    'booking_date' => $item['booking_date'],
                    'start_time' => $item['start_time'],
                    'end_time' => $item['end_time'],
                    'payment_status' => 'pending',
                    'total_price' => $session->price,
                    'status' => 'booked',
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Sessions booked successfully.',
                'data' => $bookings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $query = SessionBooking::with('coachingSession', 'coachingSession.user', 'user')
                ->where('user_id', auth()->id());

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereHas('coachingSession', function($sq) use ($search) {
                        $sq->where('title', 'LIKE', "%$search%")
                           ->orWhere('short_description', 'LIKE', "%$search%")
                           ->orWhereHas('user', function($uq) use ($search) {
                               $uq->where('first_name', 'LIKE', "%$search%")
                                  ->orWhere('last_name', 'LIKE', "%$search%");
                           });
                    })
                    ->orWhereHas('user', function($uq) use ($search) {
                        $uq->where('first_name', 'LIKE', "%$search%")
                           ->orWhere('last_name', 'LIKE', "%$search%");
                    });
                });
            }

            $bookings = $query->latest()->paginate(20);

            return response()->json([
                'status' => true,
                'data' => $bookings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getBookings($id)
    {
        try {
            $booking = SessionBooking::with('coachingSession', 'coachingSession.user', 'user')
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            return response()->json([
                'status' => true,
                'data' => $booking
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }
}
