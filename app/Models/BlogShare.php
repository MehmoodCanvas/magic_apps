<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogShare extends Model
{
    use HasFactory;
    protected $fillable = ['blog_post_id', 'user_id'];

    public function post() {
        return $this->belongsTo(FeedPost::class, 'blog_post_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
