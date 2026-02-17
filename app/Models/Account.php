<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'balance' => 'decimal:2', 
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getCurrentBalanceAttribute()
    {
        $balance = $this->opening_balance;

        $income = $this->transactions()
            ->whereRelation('category', 'type', 'income')
            ->sum('amount');

        $expense = $this->transactions()
            ->whereRelation('category', 'type', 'expense')
            ->sum('amount');

        return $balance + $income - $expense;
    }
}