<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'status',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}