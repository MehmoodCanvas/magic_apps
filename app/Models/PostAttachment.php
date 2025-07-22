<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostAttachment extends Model
{
    use HasFactory;
    protected $fillable = ['post_id', 'user_id', 'attachment_url', 'mime_type'];

    public function feedPost() {
        return $this->belongsTo(FeedPost::class, 'post_id');
    }
}
