<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skills extends Model
{
    use HasFactory;

    protected $fillable = ['skill_type_id', 'user_id', 'name', 'status', 'description'];

    public function type()
    {
        return $this->belongsTo(SkillTypes::class, 'skill_type_id');
    }

    public function attachments()
    {
        return $this->hasMany(SkillAttachments::class, 'skill_id');
    }
}
