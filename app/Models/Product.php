<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category',
        'price',
        'size',
        'stock',
        'is_available',
        'image'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'stock' => 'integer'
    ];

    // Relation avec les ventes
    public function sales()
    {
        return $this->hasMany(ProductSale::class);
    }

    // Vérifier si le produit est en stock
    public function isInStock($quantity = 1)
    {
        return $this->stock >= $quantity && $this->is_available;
    }

    // Déduire du stock
    public function decrementStock($quantity)
    {
        $this->decrement('stock', $quantity);
    }
}
