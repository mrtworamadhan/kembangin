<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $guarded = ['id']; 

    protected $casts = [
        'type' => 'string',
        'use_stock_management' => 'boolean', 
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function members()
    {
        return $this->belongsToMany(User::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role') // <--- Tambahkan ini
            ->withTimestamps();
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    
    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class);
    }
}