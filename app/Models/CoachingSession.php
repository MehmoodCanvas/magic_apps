<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoachingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'short_description',
        'long_description',
        'image',
        'video',
        'start_time',
        'end_time',
        'duration',
        'meeting_link',
        'available_days',
        'price',
        'status',
    ];

    protected $casts = [
        'available_days' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(SessionBooking::class);
    }
}
