<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'categorie_id',
        'name',
        'slug',
        'status',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
