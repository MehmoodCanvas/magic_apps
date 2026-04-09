<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AcademicPlanning;
use App\Models\AcademicPlanningAttachment;
use App\Models\AcademicSubject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class AcademicPlanningController extends Controller
{
    // List all active subjects for selection
    public function allSubjects()
    {
        try {
            $subjects = AcademicSubject::where('status', 'active')->latest()->paginate(20);
            return response()->json(['status' => true, 'data' => $subjects]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Create Planning
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'academic_subject_id' => 'required|exists:academic_subjects,id',
            'status' => 'required|in:on-going,completed',
            'description' => 'required|string',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:20480'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        try {
            $user = Auth::user();
            $planning = AcademicPlanning::create([
                'user_id' => $user->id,
                'academic_subject_id' => $request->academic_subject_id,
                'status' => $request->status,
                'description' => $request->description,
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $file->move(public_path('academic-attachments'), $filename);
                    $path = '/academic-attachments/' . $filename;
                    $mime = $file->getClientMimeType();
                    $type = str_starts_with($mime, 'image/') ? 'image' : 'video';

                    AcademicPlanningAttachment::create([
                        'academic_planning_id' => $planning->id,
                        'user_id' => $user->id,
                        'file_path' => $path,
                        'file_type' => $type,
                        'mime_type' => $mime,
                    ]);
                }
            }

            // ✅ Auto-post to Feed
            \App\Services\AutoPostService::createAcademicPlanningPost($user, $planning);

            return response()->json([
                'status' => true, 
                'message' => 'Academic Planning created successfully', 
                'data' => $planning->load('attachments', 'subject')
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // User's Plannings
    public function index(Request $request)
    {
        try {
            $query = AcademicPlanning::with('attachments', 'subject')
                ->where('user_id', Auth::id());

            // Filter: trophies = only trophy awarded, normal = only non-trophy
            if ($request->filled('filter')) {
                if ($request->filter === 'trophies') {
                    $query->where('has_trophy', true);
                } else{
                    $query->where('has_trophy', false);
                }
            }

            $plannings = $query->latest()->paginate(20);
            return response()->json(['status' => true, 'data' => $plannings]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Show Single Planning
    public function show($id)
    {
        try {
            $planning = AcademicPlanning::with('attachments', 'subject')
                ->where('user_id', Auth::id())
                ->findOrFail($id);
            return response()->json(['status' => true, 'data' => $planning]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Update Planning
    public function update(Request $request, $id)
    {
        try {
            $planning = AcademicPlanning::where('user_id', Auth::id())->findOrFail($id);

            $validator = Validator::make($request->all(), [
                 'academic_subject_id' => 'nullable|exists:academic_subjects,id',
                 'status' => 'nullable|in:on-going,completed',
                 'description' => 'nullable|string',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
            }

            $planning->update($request->only('academic_subject_id', 'status', 'description'));

            return response()->json([
                'status' => true, 
                'message' => 'Academic Planning updated successfully', 
                'data' => $planning->load('attachments', 'subject')
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Delete Planning
    public function destroy($id)
    {
        try {
            $planning = AcademicPlanning::where('user_id', Auth::id())->findOrFail($id);
            
            foreach ($planning->attachments as $attachment) {
                $filePath = public_path($attachment->file_path);
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }
            $planning->delete();

            return response()->json(['status' => true, 'message' => 'Academic Planning deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Upload Attachments
    public function uploadAttachments(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'attachments.*' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi|max:20480'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        try {
            $user = Auth::user();
            $planning = AcademicPlanning::where('user_id', $user->id)->findOrFail($id);
            $attachments = [];

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $file->move(public_path('academic-attachments'), $filename);
                    $path = '/academic-attachments/' . $filename;
                    $mime = $file->getClientMimeType();
                    $type = str_starts_with($mime, 'image/') ? 'image' : 'video';

                    $attachment = AcademicPlanningAttachment::create([
                        'academic_planning_id' => $planning->id,
                        'user_id' => $user->id,
                        'file_path' => $path,
                        'file_type' => $type,
                        'mime_type' => $mime,
                    ]);
                    $attachments[] = $attachment;
                }
            }

            return response()->json([
                'status' => true, 
                'message' => 'Attachments Uploaded Successfully', 
                'data' => $attachments
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Delete Attachment
    public function deleteAttachment(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $attachment = AcademicPlanningAttachment::where('user_id', $user->id)->findOrFail($id);

            $filePath = public_path($attachment->file_path);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
            
            $attachment->delete();

            return response()->json(['status' => true, 'message' => 'Attachment Deleted Successfully']);
        } catch (\Exception $e) {
             // If not found or other error
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
