<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price_usd',
        'price_jpy',
        'stock',
        'warehouse_id',
        'image'
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
