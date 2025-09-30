<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultations extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        // 'price',
        'status',
        // 'duration',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
        // 'duration' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(ConsultationCategories::class);
    }

    public function requests()
    {
        return $this->hasMany(ConsultationRequests::class, 'consultation_id');
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Accessors
    // public function getFormattedPriceAttribute()
    // {
    //     return $this->price ? '$' . number_format($this->price, 2) : 'Free';
    // }

    // public function getFormattedDurationAttribute()
    // {
    //     if (!$this->duration) return null;

    //     $hours = floor($this->duration / 60);
    //     $minutes = $this->duration % 60;

    //     $result = [];
    //     if ($hours > 0) $result[] = $hours . ' hour' . ($hours > 1 ? 's' : '');
    //     if ($minutes > 0) $result[] = $minutes . ' min';

    //     return implode(' ', $result);
    // }

    public function getPendingRequestsCountAttribute()
    {
        return $this->requests()->where('status', 'pending')->count();
    }

    public function getIsOwnerAttribute()
    {
        return auth()->check() && $this->user_id === auth()->id();
    }
}
