<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'mobile',
        'email',
        'password',
        'google_id',
        'is_admin',
        'role',
        'profile_picture',
        'currency',
        'onboarding_completed',
        'notification_preferences',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'password'             => 'hashed',
            'is_admin'             => 'boolean',
            'role'                 => UserRole::class,
            'onboarding_completed'      => 'boolean',
            'notification_preferences'  => 'array',
            'preferences'               => 'array',
        ];
    }

    public function wantsNotification(string $key): bool
    {
        $defaults = [
            'email' => true, 'sms' => false, 'push' => true,
            'budget_alerts' => true, 'transaction_alerts' => true,
            'weekly_summary' => false, 'bill_reminders' => true,
        ];

        $prefs = $this->notification_preferences ?? $defaults;

        return (bool) ($prefs[$key] ?? ($defaults[$key] ?? true));
    }

    /**
     * Get all categories for the user.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get all transactions for the user.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all accounts for the user.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}