<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id', // <-- TAMBAHKAN INI
        'product_name',
        'option_name',
        'quantity',
        'price',
    ];

    public $timestamps = false;

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // PEMBARUAN: Tambahkan relasi ke Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}