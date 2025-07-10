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
            'gender' => 'required|in:male,female,other',
            'born_date' => 'required|date',
            'country_id' => 'required|exists:countries,id',
            'qualification_id' => 'nullable|exists:qualifications,id',
            'employment_status_id' => 'nullable|exists:employment_statuses,id',
            'preferred_work_style_id' => 'nullable|exists:work_styles,id',
            'category_id' => 'nullable|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = $request->user();

        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $request->only([
                'gender',
                'born_date',
                'country_id',
                'qualification_id',
                'employment_status_id',
                'preferred_work_style_id',
                'category_id',
                'sub_category_id'
            ])
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

}
