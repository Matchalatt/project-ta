<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // <-- PEMBARUAN: Impor SoftDeletes

class Order extends Model
{
    // PEMBARUAN: Tambahkan SoftDeletes untuk mengaktifkan fitur hapus sementara
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'order_code',
        'customer_name',
        'customer_whatsapp',
        'delivery_method',
        'delivery_address',
        'notes',
        'total_price',
        'status',
    ];

    /**
     * Mendefinisikan bahwa satu order memiliki banyak item (OrderItem).
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Mendefinisikan bahwa satu order dimiliki oleh satu user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}