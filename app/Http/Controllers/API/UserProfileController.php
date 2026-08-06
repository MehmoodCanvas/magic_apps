<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Country;
use App\Models\EmploymentStatus;
use App\Models\Qualification;
use App\Models\SubCategory;
use App\Models\UserProfile;
use App\Models\WorkStyle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class UserProfileController extends Controller
{
    function countries() {
        return response()->json(Country::active()->get());
    }

    function qualifications() {
        return response()->json(Qualification::active()->get());
    }

    function employmentStatuses() {
        return response()->json(EmploymentStatus::active()->get());
    }

    function workStyles() {
        return response()->json(WorkStyle::active()->get());
    }

    function categories() {
        return response()->json(Category::active()->get());
    }

    function subCategories(Request $request) {

        if (isset($request->category_id)) {
            return response()->json(SubCategory::active()->where('category_id', $request->category_id)->get());
        }

        return response()->json(SubCategory::active()->get());
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'nullable|string|max:20',
            'gender' => 'required|in:male,female,other',
            'born_date' => 'required|date',
            'country_id' => 'required|exists:countries,id',
            'qualification_id' => 'nullable|exists:qualifications,id',
            'employment_status_id' => 'nullable|exists:employment_statuses,id',
            'preferred_work_style_id' => 'nullable|exists:work_styles,id',
            'category_id' => 'nullable|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'bio' => 'nullable|string|max:1000',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $request->user();

        // Update User
        $user->update([
            'first_name' => $request->first_name,
            'last_name'  => $request->last_name,
            'phone'      => $request->phone,
        ]);

        $profileData = $request->only([
            'gender',
            'born_date',
            'country_id',
            'qualification_id',
            'employment_status_id',
            'preferred_work_style_id',
            'category_id',
            'sub_category_id',
            'bio'
        ]);


        // Handle resume upload
        if ($request->hasFile('resume')) {
            // Delete old resume if exists
            $existingProfile = $user->profile;
            if ($existingProfile && $existingProfile->resume) {
                $oldPath = public_path($existingProfile->resume);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $file = $request->file('resume');
            $filename = time() . '_resume_' . $file->getClientOriginalName();
            $file->move(public_path('resumes'), $filename);
            $profileData['resume'] = '/resumes/' . $filename;
        }

        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $profileData
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile->load([
                'country',
                'qualification',
                'employmentStatus',
                'preferredWorkStyle',
                'category',
                'subCategory'
            ])
        ]);
    }

    // Update Profile Picture
    public function updateProfilePicture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        $user = $request->user();
        $profile = $user->profile;

        // Delete old profile picture if exists
        if ($profile && $profile->profile_picture) {
            $oldPath = public_path($profile->profile_picture);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $file = $request->file('profile_picture');
        $filename = time() . '_pp_' . $file->getClientOriginalName();
        $file->move(public_path('profile_pictures'), $filename);
        $picturePath = '/profile_pictures/' . $filename;

        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['profile_picture' => $picturePath]
        );

        return response()->json([
            'status' => true,
            'message' => 'Profile picture set successfully',
            'profile_picture' => $picturePath,
        ]);
    }

    // Delete Profile Picture
    public function deleteProfilePicture(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile || !$profile->profile_picture) {
            return response()->json(['status' => false, 'message' => 'No profile picture found'], 404);
        }

        $oldPath = public_path($profile->profile_picture);
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }

        $profile->update(['profile_picture' => null]);

        return response()->json([
            'status' => true,
            'message' => 'Profile picture remove successfully',
        ]);
    }

    // Update Cover Image
    public function updateCoverImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cover_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        $user = $request->user();
        $profile = $user->profile;

        // Delete old cover image if exists
        if ($profile && $profile->cover_image) {
            $oldPath = public_path($profile->cover_image);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $file = $request->file('cover_image');
        $filename = time() . '_cover_' . $file->getClientOriginalName();
        $file->move(public_path('cover_images'), $filename);
        $coverPath = '/cover_images/' . $filename;

        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['cover_image' => $coverPath]
        );

        return response()->json([
            'status' => true,
            'message' => 'Cover image set successfully',
            'cover_image' => $coverPath,
        ]);
    }

    // Delete Cover Image
    public function deleteCoverImage(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!$profile || !$profile->cover_image) {
            return response()->json(['status' => false, 'message' => 'No cover image found'], 404);
        }

        $oldPath = public_path($profile->cover_image);
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }

        $profile->update(['cover_image' => null]);

        return response()->json([
            'status' => true,
            'message' => 'Cover image remove successfully',
        ]);
    }

    // Search Users by name or email
    public function searchUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        $query = $request->query('query');

        $users = \App\Models\User::where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
            })
            ->select('id', 'first_name', 'last_name', 'email')
            ->with('profile:id,user_id,profile_picture')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $users,
        ]);
    }

}
