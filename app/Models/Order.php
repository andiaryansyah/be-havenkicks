<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model {
    protected $fillable = ['user_id','order_code','total_price','status','shipping_address','snap_token','payment_url'];
    public function items(){ return $this->hasMany(OrderItem::class); }
    public function user(){ return $this->belongsTo(User::class); }
}
