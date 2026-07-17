<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Story;
use App\Models\StoryAttachment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class StoryController extends Controller
{
    // Fetch all active stories
    public function index()
    {
        try {
            $stories = Story::with(['user:id,first_name,last_name,email', 'attachments'])
                ->where('expires_at', '>', Carbon::now())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $stories
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Create a new story
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'caption' => 'nullable|string',
            'attachments' => 'required|array|max:10', // maximum 10 files per story
            'attachments.*' => 'file|mimes:jpeg,png,jpg,gif,mp4,mov,avi,wmv|max:20480', // max 20MB per file
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $user = $request->user();

            $story = Story::create([
                'user_id' => $user->id,
                'caption' => $request->caption,
                'expires_at' => Carbon::now()->addHours(24),
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $extension = $file->extension();
                    $filename = time() . '_' . uniqid() . '.' . $extension;
                    $file->move(public_path('uploads/stories'), $filename);

                    $fileType = in_array(strtolower($extension), ['mp4', 'mov', 'avi', 'wmv']) ? 'video' : 'image';

                    StoryAttachment::create([
                        'story_id' => $story->id,
                        'file_path' => 'uploads/stories/' . $filename,
                        'file_type' => $fileType,
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Story created successfully',
                'data' => $story->load('attachments')
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Delete a full story
    public function destroy(Request $request, $id)
    {
        try {
            $story = Story::where('id', $id)->where('user_id', $request->user()->id)->first();

            if (!$story) {
                return response()->json(['status' => false, 'message' => 'Story not found or unauthorized'], 404);
            }

            // Remove files from storage
            foreach ($story->attachments as $attachment) {
                $filePath = public_path($attachment->file_path);
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }

            $story->delete(); // This will also delete attachments from DB due to cascade

            return response()->json([
                'status' => true,
                'message' => 'Story deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Update story (e.g. caption or add new files)
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'caption' => 'nullable|string',
            'new_attachments' => 'nullable|array|max:10',
            'new_attachments.*' => 'file|mimes:jpeg,png,jpg,gif,mp4,mov,avi,wmv|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $story = Story::where('id', $id)->where('user_id', $request->user()->id)->first();

            if (!$story) {
                return response()->json(['status' => false, 'message' => 'Story not found or unauthorized'], 404);
            }

            if ($request->has('caption')) {
                $story->caption = $request->caption;
                $story->save();
            }

            if ($request->hasFile('new_attachments')) {
                foreach ($request->file('new_attachments') as $file) {
                    $extension = $file->extension();
                    $filename = time() . '_' . uniqid() . '.' . $extension;
                    $file->move(public_path('uploads/stories'), $filename);

                    $fileType = in_array(strtolower($extension), ['mp4', 'mov', 'avi', 'wmv']) ? 'video' : 'image';

                    StoryAttachment::create([
                        'story_id' => $story->id,
                        'file_path' => 'uploads/stories/' . $filename,
                        'file_type' => $fileType,
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Story updated successfully',
                'data' => $story->load('attachments')
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Delete a specific attachment
    public function deleteAttachment(Request $request, $id)
    {
        try {
            $attachment = StoryAttachment::with('story')->find($id);

            if (!$attachment || $attachment->story->user_id !== $request->user()->id) {
                return response()->json(['status' => false, 'message' => 'Attachment not found or unauthorized'], 404);
            }

            $filePath = public_path($attachment->file_path);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            $attachment->delete();

            return response()->json([
                'status' => true,
                'message' => 'Story attachment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
