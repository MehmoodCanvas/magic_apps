<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicPlanningAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_planning_id',
        'user_id',
        'file_path',
        'file_type',
        'mime_type',
    ];

    public function planning()
    {
        return $this->belongsTo(AcademicPlanning::class, 'academic_planning_id');
    }
}
