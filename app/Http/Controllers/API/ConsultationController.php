<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ConsultationCategories;
use App\Models\Consultations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\ConsultationRequest;
use App\Models\ConsultationRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    /**
     * Get all active categories
     */
    public function categoriesList(Request $r)
    {
        try {
            $categories = ConsultationCategories::active()->latest()->paginate($r->get('per_page',20));

            return response()->json([
                'status' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single category with Consultationss
     */
    public function singleCategory($slug)
    {
        try {
            $category = ConsultationCategories::where('slug', $slug)->with(['Consultationss' => function($q) {
                    $q->active()->with('user')->latest()->take(10);
                }])
                ->firstOrFail();

            return response()->json([
                'status' => true,
                'data' => $category
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

     /**
     * Create new Consultations (Offer or Request)
     */
    public function CreateConsultations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:consultation_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'status'        => 'in:active,inactive',
            'duration' => 'nullable|integer|min:15|max:480',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false,'message' => $validator->errors()], 422);
        }

        try {
            $Consultations = Consultations::create([
                'user_id' => Auth::id(),
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'duration' => $request->duration,
                'tags' => $request->tags,
                'status' => $request->status,
            ]);

            return response()->json([
                'status' => true,
                'data' => $Consultations->load(['user', 'category'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


      /**
     * Update Consultations
     */
    public function updateConsultations(Request $request, $id)
    {
         $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:consultation_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'status'        => 'in:active,inactive',
            'duration' => 'nullable|integer|min:15|max:480',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false,'message' => $validator->errors()], 422);
        }

        try {
            $Consultations = Consultations::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
            $Consultations->update($request->only(['category_id','title','description','duration','tags','status']));

            return response()->json([
                'status' => true,
                'data' => $Consultations->load(['user', 'category'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Consultations
     */
    public function destroyConsultations($id)
    {
        try {
            $Consultations = Consultations::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

            // Check if there are pending requests
            if ($Consultations->requests()->pending()->exists()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete Consultations with pending requests'
                ], 400);
            }

            $Consultations->delete();

            return response()->json([
                'status' => true,
                'message' => 'Consultations deleted statusfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


     /**
     * Get all Consultationss with filters
     */
    public function allConsultations(Request $request)
    {
        try {
            $category = $request->get('category_id');
            $search = $request->get('search');
            $status = $request->get('status', 'active');
            $per_page = $request->get('per_page', 15);

            $query = Consultations::with(['user', 'category'])->withCount('requests')->where('status', $status);

            // Filter by category
            if ($category) {
                $query->byCategory($category);
            }

            // Search
            if ($search) {
                $query->search($search);
            }

            $Consultationss = $query->latest()->paginate($per_page);

            return response()->json([
                'status' => true,
                'data' => $Consultationss
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's own Consultationss (My Offers & My Requests)
     */
    public function myConsultations(Request $request)
    {
        try {
            $status = $request->get('status');
            $per_page = $request->get('per_page', 15);


            $query = Consultations::with(['category', 'requests.requester'])->where('user_id', Auth::id());

            if ($status) {
                $query->where('status', $status);
            }

            $Consultationss = $query->latest()->paginate($per_page);

            return response()->json([
                'status' => true,
                'data' => $Consultationss
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get single Consultations details
     */
    public function singleConsultations($id)
    {
        try {
            $Consultations = Consultations::with(['user', 'category', 'requests' => function($q) {
                $q->with('requester')->latest();
            }])
            ->findOrFail($id);


            return response()->json([
                'status' => true,
                'data' => $Consultations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }


     /**
     * Create new request for a consultation
     */
    public function StoreConsultationRequest(Request $request, $consultationId)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            $consultation = Consultations::where('id', $consultationId)->where('status', 'active')->firstOrFail();

            // Check if user is not the consultation owner
            if ($consultation->user_id === Auth::id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot request your own consultation'
                ], 400);
            }

            // Check if user already sent a pending request
            $existingRequest = ConsultationRequests::where('consultation_id', $consultationId)
                ->where('requester_id', Auth::id())
                ->where('status', 'pending')
                ->first();

            if ($existingRequest) {
                return response()->json([
                    'status' => false,
                    'message' => 'You already have a pending request for this consultation'
                ], 400);
            }

            DB::beginTransaction();

            $consultationRequest = ConsultationRequests::create([
                'consultation_id' => $consultationId,
                'requester_id' => Auth::id(),
                'message' => $request->message,
                // 'preferred_datetime' => $request->preferred_datetime,
                'status' => 'pending'
            ]);

            // Send notification to consultation owner
            // Notification logic here

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Request sent statusfully',
                'data' => $consultationRequest->load(['consultation', 'requester'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

     /**
     * Get user's sent requests
     */
    public function myRequests(Request $request)
    {
        try {
            $status = $request->get('status');

            $query = ConsultationRequests::with(['consultation.user', 'consultation.category'])
                ->where('requester_id', Auth::id());

            if ($status) {
                $query->where('status', $status);
            }

            $requests = $query->latest()->paginate(15);

            return response()->json([
                'status' => true,
                'data' => $requests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Get received requests (for consultation offers I created)
     */
    public function receivedRequests(Request $request)
    {
        try {
            $status = $request->get('status');

            $query = ConsultationRequests::with(['consultation', 'requester'])
                ->whereHas('consultation', function($q) {
                    $q->where('user_id', Auth::id());
                });

            if ($status) {
                $query->where('status', $status);
            }

            $requests = $query->latest()->paginate(15);

            return response()->json([
                'status' => true,
                'data' => $requests
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

     /**
     * Get single request details
     */
    public function singleRequest($requestId)
    {
        try {
            $consultationRequest = ConsultationRequests::with(['consultation.user', 'consultation.category', 'requester'])
                ->findOrFail($requestId);

            // Verify user has access
            if ($consultationRequest->consultation->user_id !== Auth::id()
                && $consultationRequest->requester_id !== Auth::id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            return response()->json([
                'status' => true,
                'data' => $consultationRequest
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }


    /**
     * Accept a request (for consultation owner)
     */
    public function updateRequest(Request $request)
    {
         $validator = Validator::make($request->all(), [
            'request_id' => 'required|exists:consultation_requests,id',
            'status'        => 'in:accepted,rejected,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false,'message' => $validator->errors()], 422);
        }

        try {
            $consultationRequest = ConsultationRequests::with('consultation')
                ->findOrFail($request->get('request_id'));

            // Verify user is consultation owner
            if ($consultationRequest->consultation->user_id !== Auth::id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized action'
                ], 403);
            }

            if ($request->get('status') == 'accepted' || $request->get('status') == 'rejected') {

                // Check if request is pending
                if ($consultationRequest->status !== 'pending') {
                    return response()->json([
                        'status' => false,
                        'message' => 'This request cannot be accepted the already completed'
                    ], 400);
                }

                $consultationRequest->update([
                    'status' => $request->get('status'),
                    'accepted_at' => now()
                ]);

            } else {

                // Check if request is accepted
                if ($consultationRequest->status !== 'accepted') {
                    return response()->json([
                        'status' => false,
                        'message' => 'Only accepted requests can be completed'
                    ], 400);
                }

                $consultationRequest->update([
                    'status' => $request->get('status'),
                    'completed_at' => now(),
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $consultationRequest->load(['consultation', 'requester'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
