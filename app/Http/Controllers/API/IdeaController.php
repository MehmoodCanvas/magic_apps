<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Idea;
use App\Models\IdeaAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Storage;

class IdeaController extends Controller
{
    //  Add Idea
    public function store(Request $request)
    {

         $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'improvement' => 'nullable|string',
            'benefits' => 'nullable|string',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,mp4,pdf|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        try {
            $user = $request->user();
            $idea = Idea::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'improvement' => $request->improvement,
                'benefits' => $request->benefits,
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    // $path = $file->store('attachments');
                    $filename = time() . $file->getClientOriginalName();
                    $file->move(public_path('ideas-attachment'), $filename);
                    $path = '/ideas-attachment/'.$filename;
                    $mime = $file->getClientMimeType();

                    IdeaAttachment::create([
                        'idea_id' => $idea->id,
                        'user_id'   => $user->id,
                        'file_path' => $path,
                        'mime_type' => $mime,
                    ]);
                }
            }

            return response()->json(['status' => true, 'message' => 'Idea created successfully', 'data' => $idea->load('attachments', 'user')]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    //  Edit Idea
    public function singleIdea($id)
    {
        $idea = Idea::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        return response()->json(['status' => true, 'data' => $idea->load('attachments', 'user')]);
    }

    //  Edit Idea
    public function update(Request $request, $id)
    {
        $idea = Idea::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        $idea->update($request->only('title', 'description', 'improvement', 'benefits'));

        return response()->json(['status' => true, 'message' => 'Idea updated successfully', 'data' => $idea->load('attachments', 'user')]);
    }

    //  Upload Attachments separately
    public function uploadAttachments(Request $request, $idea_id)
    {
        $user = $request->user();
        $idea = Idea::where('user_id', $user->id)->findOrFail($idea_id);

        $validator = Validator::make($request->all(), [
            'attachments.*' => 'required|file|mimes:jpg,jpeg,png,gif,mp4,pdf|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        try {
            $attachments = [];
            foreach ($request->file('attachments') as $file) {
                // $path = $file->store('attachments');
                $filename = time() . $file->getClientOriginalName();
                $file->move(public_path('ideas-attachment'), $filename);
                $path = '/ideas-attachment/'.$filename;
                $mime = $file->getClientMimeType();

                $attachment = IdeaAttachment::create([
                    'idea_id' => $idea->id,
                    'user_id'   => $user->id,
                    'file_path' => $path,
                    'mime_type' => $mime,
                ]);
                $attachments[] = $attachment;
            }

            return response()->json(['status' => true, 'message' => 'Attachments Uploaded Successfully', 'data' => $attachments]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    //  Delete Single Attachment
    public function deleteAttachment(Request $request, $id)
    {
        $user = $request->user();
        $attachment = IdeaAttachment::where('user_id', $user->id)->findOrFail($id);

        $filePath = public_path($attachment->file_path);
        if (File::exists($filePath)) {
            File::delete($filePath);
            $attachment->delete();
        } else {
            return response()->json(['status' => false, 'message' => 'Attachment Not Found']);
        }

        return response()->json(['status' => true, 'message' => 'Attachment Deleted Successfully']);
    }

    //  Delete Idea
    public function destroy($id)
    {
        $idea = Idea::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        foreach ($idea->attachments as $attachment) {
            $filePath = public_path($attachment->file_path);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }
        $idea->delete();
        return response()->json(['status' => true, 'message' => 'Idea deleted successfully']);
    }

    //  My Ideas (user-specific)
    public function myIdeas()
    {
        $ideas = Idea::with('attachments', 'user')->where('user_id', Auth::id())->latest()->paginate(10);
        return response()->json(['status' => true, 'data' => $ideas]);
    }

    //  All Ideas (all users)
    public function allIdeas()
    {
        $ideas = Idea::with(['user', 'attachments'])->latest()->paginate(10);
        return response()->json(['status' => true, 'data' => $ideas]);
    }
}
