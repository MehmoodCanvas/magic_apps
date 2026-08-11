<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\CoachingSession;
use App\Models\SessionBooking;
use App\Models\Product; // In case we need it for something else, but mainly ThawaniService
use App\Services\ThawaniService;

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

            DB::beginTransaction();
            $bookings = [];
            $thawaniProducts = [];
            $totalAmount = 0;

            foreach ($request->items as $item) {
                $session = CoachingSession::findOrFail($item['session_id']);
                $totalAmount += $session->price;

                $bookings[] = SessionBooking::create([
                    'user_id' => auth()->id(),
                    'coaching_session_id' => $session->id,
                    'booking_date' => $item['booking_date'],
                    'start_time' => $item['start_time'],
                    'end_time' => $item['end_time'],
                    'payment_status' => 'unpaid',
                    'total_price' => $session->price,
                    'status' => 'pending',
                ]);

                $thawaniProducts[] = [
                    'name' => $session->title,
                    'quantity' => 1,
                    'unit_amount' => (int)($session->price * 1000), // Convert OMR to baisa
                ];
            }

            // Create Thawani checkout session
            $thawaniService = new ThawaniService();
            $bookingIds = implode(',', array_column($bookings, 'id'));
            
            $thawaniResult = $thawaniService->createCheckoutSession([
                'client_reference_id' => 'bookings-' . $bookingIds . '-' . time(),
                'products' => $thawaniProducts,
                'success_url' => url('/api/session-bookings/payment/success?booking_ids=' . $bookingIds),
                'cancel_url' => url('/api/session-bookings/payment/cancel?booking_ids=' . $bookingIds),
                'metadata' => [
                    'user_id' => (string)auth()->id(),
                    'booking_ids' => $bookingIds,
                ],
            ]);

            if (!$thawaniResult['status']) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Payment session creation failed: ' . ($thawaniResult['message'] ?? 'Unknown error'),
                ], 400);
            }

            // Save Thawani session info in bookings
            $sessionId = $thawaniResult['session_id'];
            foreach ($bookings as $booking) {
                $booking->update([
                    'thawani_session_id' => $sessionId,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sessions booked. Redirect to payment.',
                'data' => [
                    'bookings' => $bookings,
                    'total_price' => $totalAmount,
                    'thawani_session_id' => $sessionId,
                    'redirect_url' => $thawaniResult['redirect_url'],
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /session-bookings/payment/success
     */
    public function bookingPaymentSuccess(Request $request)
    {
        $bookingIds = $request->query('booking_ids');

        if (!$bookingIds) {
            return response('<h1>Error: Booking IDs missing</h1>', 400);
        }

        $ids = explode(',', $bookingIds);
        $bookings = SessionBooking::whereIn('id', $ids)->get();
        $sessionId = null;

        if ($bookings->isNotEmpty()) {
            $firstBooking = $bookings->first();
            $sessionId = $firstBooking->thawani_session_id;

            if ($sessionId && $firstBooking->payment_status !== 'paid') {
                $thawaniService = new ThawaniService();
                $result = $thawaniService->getSession($sessionId);

                if ($result['status'] && isset($result['data']['payment_status']) && $result['data']['payment_status'] === 'paid') {
                    foreach ($bookings as $booking) {
                        $booking->update([
                            'payment_status' => 'paid',
                            'status' => 'booked',
                            'transaction_id' => $sessionId,
                            'payment_method' => 'thawani'
                        ]);
                    }
                }
            }
        }

        return response()->view('payments.thawani-redirect', [
            'status' => 'success',
            'order_id' => $bookingIds, // Using order_id variable for view compatibility
            'message' => 'Payment Successful!',
            'deep_link' => "magicapp://api/session-bookings/payment/success?booking_ids=" . $bookingIds . ($sessionId ? "&thawani_session_id=" . $sessionId : ""),
        ]);
    }

    /**
     * GET /session-bookings/payment/cancel
     */
    public function bookingPaymentCancel(Request $request)
    {
        $bookingIds = $request->query('booking_ids');
        $sessionId = null;

        if ($bookingIds) {
            $ids = explode(',', $bookingIds);
            $bookings = SessionBooking::whereIn('id', $ids)->get();
            
            if ($bookings->isNotEmpty()) {
                $sessionId = $bookings->first()->thawani_session_id;
            }
            
            foreach ($bookings as $booking) {
                if ($booking->payment_status !== 'paid') {
                    $booking->update([
                        'payment_status' => 'cancelled',
                        'status' => 'cancelled',
                    ]);
                }
            }
        }

        return response()->view('payments.thawani-redirect', [
            'status' => 'cancelled',
            'order_id' => $bookingIds,
            'message' => 'Payment Cancelled',
            'deep_link' => "magicapp://api/session-bookings/payment/cancel?booking_ids=" . $bookingIds . ($sessionId ? "&thawani_session_id=" . $sessionId : ""),
        ]);
    }

    /**
     * GET /session-bookings/payment/verify/{thawani_session_id}
     */
    public function verifyBookingPayment($thawaniSessionId)
    {
        $bookings = SessionBooking::with('coachingSession')
            ->where('user_id', auth()->id())
            ->where('thawani_session_id', $thawaniSessionId)
            ->get();

        if ($bookings->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Bookings not found.'], 404);
        }

        $firstBooking = $bookings->first();

        // If not verified yet
        if (!in_array($firstBooking->payment_status, ['paid', 'cancelled'])) {
            $thawaniService = new ThawaniService();
            $result = $thawaniService->getSession($thawaniSessionId);

            if ($result['status'] && isset($result['data']['payment_status'])) {
                $thawaniStatus = $result['data']['payment_status'];

                foreach ($bookings as $booking) {
                    if ($thawaniStatus === 'paid') {
                        $booking->update([
                            'payment_status' => 'paid',
                            'status' => 'booked',
                            'transaction_id' => $thawaniSessionId,
                            'payment_method' => 'thawani'
                        ]);
                    } elseif ($thawaniStatus === 'cancelled') {
                        $booking->update([
                            'payment_status' => 'cancelled',
                            'status' => 'cancelled'
                        ]);
                    }
                }
                $bookings = $bookings->fresh();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Payment status: ' . $bookings->first()->payment_status,
            'data' => $bookings,
        ]);
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

    /**
     * Get all bookings received on sessions created by the current user.
     */
    public function mySessionBookings(Request $request)
    {
        try {
            // Get IDs of sessions created by the current user
            $mySessionIds = CoachingSession::where('user_id', auth()->id())->pluck('id');

            $query = SessionBooking::with('coachingSession', 'user')
                ->whereIn('coaching_session_id', $mySessionIds);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereHas('user', function($uq) use ($search) {
                        $uq->where('first_name', 'LIKE', "%$search%")
                           ->orWhere('last_name', 'LIKE', "%$search%");
                    })
                    ->orWhereHas('coachingSession', function($sq) use ($search) {
                        $sq->where('title', 'LIKE', "%$search%");
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
}
