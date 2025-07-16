<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkStyle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
