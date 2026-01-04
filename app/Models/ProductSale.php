<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSale extends Model
{
    protected $fillable = [
        'product_id',
        'staff_id',
        'quantity',
        'unit_price',
        'total_price',
        'payment_method',
        'sale_date'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'quantity' => 'integer',
        'sale_date' => 'datetime'
    ];

    // Relation avec le produit
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relation avec l'agent
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
