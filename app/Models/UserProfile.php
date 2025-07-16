<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

     protected $fillable = [
        'user_id', 'gender', 'born_date',
        'country_id', 'qualification_id', 'employment_status_id',
        'preferred_work_style_id', 'category_id', 'sub_category_id'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function country() {
        return $this->belongsTo(Country::class);
    }

    public function qualification() {
        return $this->belongsTo(Qualification::class);
    }

    public function employmentStatus() {
        return $this->belongsTo(EmploymentStatus::class);
    }

    public function preferredWorkStyle() {
        return $this->belongsTo(WorkStyle::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

    public function subCategory() {
        return $this->belongsTo(SubCategory::class);
    }
}
