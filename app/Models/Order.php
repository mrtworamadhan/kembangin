<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'order_date' => 'date',
        'due_date' => 'date',
    ];

    // --- RELATIONS ---

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getPaidAmountAttribute()
    {
        return $this->transactions()->sum('amount');
    }

    public function getDueAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }
}