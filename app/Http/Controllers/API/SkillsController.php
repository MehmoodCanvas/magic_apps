<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SkillAttachments;
use App\Models\Skills;
use App\Models\SkillTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class SkillsController extends Controller
{
           // ✅ Get all skill types
    public function getTypes()
    {
        return response()->json(['status' => true, 'data' => SkillTypes::all()]);
    }

    // ✅ Create Skill Type
    public function createType(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'icon' => 'nullable|image|max:2048',
            'status' => 'required|boolean'
        ]);

        $iconPath = $request->hasFile('icon') ? $request->file('icon')->store('icons', 'public') : null;

        $type = SkillTypes::create([
            'name' => $request->name,
            'description' => $request->description,
            'icon' => $iconPath,
            'status' => $request->status
        ]);

        return response()->json(['message' => 'Skill Type Created Successfully', 'data' => $type]);
    }

    // ✅ Get all skills with pagination + relationships
    public function index(Request $request)
    {
        $user = $request->user();
        $skills = Skills::with(['type', 'attachments'])->where('user_id', $user->id)
            ->when($request->skill_type_id, fn($q) => $q->where('skill_type_id', $request->skill_type_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))->latest()
            ->paginate(10);

        return response()->json(['status' => true, 'data' => $skills]);
    }

    // ✅ Get Single Skill
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $skill = Skills::with(['type', 'attachments'])->where('user_id', $user->id)->findOrFail($id);
        return response()->json(['status' => true, 'data' => $skill]);
    }

    // ✅ Create Skill with multiple attachments
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'skill_type_id' => 'required|exists:skill_types,id',
            'name' => 'required|string',
            'status' => 'in:on-going,completed',
            'description' => 'nullable|string',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,gif|max:10240' // 10MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        $user = $request->user();
        $data = $request->only(['skill_type_id', 'name', 'status', 'description']);
        $data['user_id'] = $user->id;

        $skill = Skills::create($data);

         // ✅ Upload & Save Attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                // $path = $file->store('attachments');
                $filename = time() . $file->getClientOriginalName();
                $file->move(public_path('skills-attachment'), $filename);
                $path = '/skills-attachment/'.$filename;
                $mime = $file->getClientMimeType();

                SkillAttachments::create([
                    'skill_id' =>  $skill->id,
                    'user_id'   => $user->id,
                    'file_path' => $path,
                    'mime_type' => $mime,
                ]);
            }
        }

        return response()->json(['status' => true, 'message' => 'Skill Created Successfully', 'data' => $skill->load('type','attachments')]);
    }

    // ✅ Update Skill
    public function update(Request $request, $id)
    {
        $skill = Skills::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'status' => 'in:on-going,completed',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }
        $skill->update($request->only(['name', 'status', 'description']));

        return response()->json(['status' => true, 'message' => 'Skill Updated Successfully', 'data' => $skill->load('type','attachments')]);
    }

    // ✅ Delete Skill
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $skill = Skills::where('user_id', $user->id)->findOrFail($id);

        // Delete attachments from storage
        foreach ($skill->attachments as $attachment) {
            // Storage::disk('public')->delete($attachment->file_path);
            $filePath = public_path($attachment->file_path);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
        }

        $skill->delete();
        return response()->json(['status' => true, 'message' => 'Skill Deleted Successfully']);
    }

    // ✅ Upload Attachments separately
    public function uploadAttachments(Request $request, $skill_id)
    {
        $user = $request->user();
        $skill = Skills::where('user_id', $user->id)->findOrFail($skill_id);

        $request->validate([
            'attachments.*' => 'required|file|mimes:jpg,jpeg,png,mp4,mov,gif|max:10240'
        ]);

        $attachments = [];
        foreach ($request->file('attachments') as $file) {
            // $path = $file->store('attachments');
            $filename = time() . $file->getClientOriginalName();
            $file->move(public_path('skills-attachment'), $filename);
            $path = '/skills-attachment/'.$filename;
            $mime = $file->getClientMimeType();

            $attachment = SkillAttachments::create([
                'skill_id' =>  $skill->id,
                'user_id'   => $user->id,
                'file_path' => $path,
                'mime_type' => $mime,
            ]);
            $attachments[] = $attachment;
        }

        return response()->json(['status' => true, 'message' => 'Attachments Uploaded Successfully', 'data' => $attachments]);
    }

    // ✅ Delete Single Attachment
    public function deleteAttachment(Request $request, $id)
    {
        $user = $request->user();
        $attachment = SkillAttachments::where('user_id', $user->id)->findOrFail($id);

        $filePath = public_path($attachment->file_path);
        if (File::exists($filePath)) {
            File::delete($filePath);
            $attachment->delete();
        } else {
            return response()->json(['status' => false, 'message' => 'Attachment Not Found']);
        }

        return response()->json(['status' => true, 'message' => 'Attachment Deleted Successfully']);
    }
}
