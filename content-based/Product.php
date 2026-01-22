<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'description',
        'image',
        'category',
        'tags', // <-- [TAMBAHAN] Daftarkan kolom baru di sini
        'is_available',
    ];

    /**
     * Mendefinisikan bahwa satu produk bisa memiliki banyak opsi/varian.
     */
    public function options()
    {
        return $this->hasMany(ProductOption::class);
    }
}