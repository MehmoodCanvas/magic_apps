<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillTypes extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'icon', 'status'];

    public function skills(){
        return $this->hasMany(Skills::class);
    }
}
