<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'status',
        'has_seen_tour',
        'avatar_url'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
    public function businesses()
    {
        return $this->belongsToMany(Business::class)
            ->withPivot('role')
            ->withTimestamps();
    }
    public function getTenants(Panel $panel): Collection
    {
        return $this->businesses;
    }

        public function canAccessTenant(Model $tenant): bool
    {
        return $this->businesses->contains($tenant);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->role === 'admin' || $this->email === 'owner@kembangin.com';
        }

        return true; 
    }

    public function personalAccounts()
    {
        return $this->hasMany(Account::class)->whereNull('business_id');
    }

    public function personalCategories()
    {
        return $this->hasMany(Category::class)->whereNull('business_id');
    }

    public function personalTransactions()
    {
        return $this->hasMany(Transaction::class)->whereNull('business_id');
    }

    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    public function getFamilyIdsAttribute(): array
    {
        if ($this->household_id) {
            return self::where('household_id', $this->household_id)
                       ->orWhere('id', $this->household_id)
                       ->pluck('id')
                       ->toArray();
        }

        return [$this->id];
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}