<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    use HasFactory;

    protected $fillable=['order_id','platform','transaction_id','amount','status','meta'];
    protected $casts=['meta'=>'array'];
}
