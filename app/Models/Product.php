<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['category_id','name','slug','sku','description','price','sale_price','stock','status'];
    protected $casts = ['status'=>'boolean'];

    public function category(){
        return $this->belongsTo(Category::class);
    }

    public function images(){
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function attributeValues(){
        return $this->belongsToMany(AttributeValue::class,'product_attribute_values');
    }

    // public function productAttributeValue(){
    //     return $this->hasMany(ProductAttributeValue::class);
    // }

    public function scopeActive($q){
        return $q->where('status',1); }
}
