<?php

namespace App\Models;

use App\Support\AdminAccess;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'username',
        'password',
        'password_hash',
        'role',
        'status',
        'citizen_id',
        'phone',
        'birth_date',
        'address_line',
        'province',
        'district',
        'subdistrict',
        'postcode',
        'farmer_code',
        'registered_at',
        'registered_province',
        'farm_province',
        'farm_area_rai',
        'farm_area_ngan',
        'farm_area_square_wa',
        'crop_type',
        'member_registered_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    public function farmerProfile(): HasOne
    {
        return $this->hasOne(FarmerProfile::class);
    }

    public function apiAccessTokens(): HasMany
    {
        return $this->hasMany(ApiAccessToken::class);
    }

    public function getAuthPassword(): ?string
    {
        return $this->password_hash ?: ($this->attributes['password'] ?? null);
    }

    public function isSuperAdmin(): bool
    {
        return AdminAccess::isSuperAdmin($this);
    }

    public function canAccessAdminPanel(): bool
    {
        return AdminAccess::canAccessAdminPanel($this);
    }
}
