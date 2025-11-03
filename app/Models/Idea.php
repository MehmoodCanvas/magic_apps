<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Idea extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'description', 'improvement', 'benefits'
    ];

    public function attachments()
    {
        return $this->hasMany(IdeaAttachment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
