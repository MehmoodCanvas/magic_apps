<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicPlanning extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'academic_subject_id',
        'status',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->belongsTo(AcademicSubject::class, 'academic_subject_id');
    }

    public function attachments()
    {
        return $this->hasMany(AcademicPlanningAttachment::class);
    }
}
