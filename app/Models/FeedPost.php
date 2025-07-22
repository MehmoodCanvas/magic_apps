<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedPost extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'original_post_id', 'content','is_shared', 'is_published', 'scheduled_at'];

    public function attachments() {
        return $this->hasMany(PostAttachment::class, 'post_id');
    }

    public function comments() {
        return $this->hasMany(PostComment::class, 'post_id');
    }

    public function likes() {
        return $this->hasMany(PostLike::class, 'post_id');
    }

    public function shares() {
       return $this->hasMany(FeedPost::class, 'original_post_id');
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function sharedPosts()
    {
        return $this->belongsTo(FeedPost::class, 'original_post_id');
    }

    // public function sharedPosts()
    // {
    //     return $this->hasMany(FeedPost::class, 'original_post_id');
    // }
}
