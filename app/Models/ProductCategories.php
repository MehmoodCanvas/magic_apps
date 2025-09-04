<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategories extends Model
{
    use HasFactory;

    protected $fillable = ['name','slug','image','description','status','parent_id'];
    public function products(){ return $this->hasMany(Product::class); }
    public function parent(){ return $this->belongsTo(Category::class,'parent_id'); }
    public function children(){ return $this->hasMany(Category::class,'parent_id'); }
}
