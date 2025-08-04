<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillAttachments extends Model
{
    use HasFactory;

    protected $fillable = ['skill_id', 'user_id', 'file_path', 'mime_type'];

    public function skill()
    {
        return $this->belongsTo(Skills::class);
    }
}
