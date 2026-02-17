<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    // Helper untuk hitung total
    public function calculateTotal()
    {
        return $this->items->sum('subtotal');
    }
}