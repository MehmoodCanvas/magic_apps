<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'coaching_session_id',
        'booking_date',
        'start_time',
        'end_time',
        'payment_status',
        'payment_method',
        'transaction_id',
        'total_price',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coachingSession()
    {
        return $this->belongsTo(CoachingSession::class);
    }
}
