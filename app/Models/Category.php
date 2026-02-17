<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

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
}