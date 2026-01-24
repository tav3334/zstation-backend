<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function machines()
    {
        return $this->hasMany(Machine::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function gameSessions()
    {
        return $this->hasMany(GameSession::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function productSales()
    {
        return $this->hasMany(ProductSale::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function cashRegisters()
    {
        return $this->hasMany(CashRegister::class);
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }
}
