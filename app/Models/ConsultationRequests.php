<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationRequests extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'requester_id',
        'message',
        // 'preferred_datetime',
        'status',
        'accepted_at',
        'completed_at',
    ];

    protected $casts = [
        // 'preferred_datetime' => 'datetime',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function consultation()
    {
        return $this->belongsTo(Consultations::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

}
