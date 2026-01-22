<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'image',
    ];

    public function academicPlannings()
    {
        return $this->hasMany(AcademicPlanning::class);
    }
}
