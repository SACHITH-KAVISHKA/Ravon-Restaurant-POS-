<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'password',
        'pin',
        'is_active',
        'last_login_at',
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
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get orders created by this user.
     */
    public function createdOrders()
    {
        return $this->hasMany(Order::class, 'created_by');
    }

    /**
     * Get orders where user is the waiter.
     */
    public function waiterOrders()
    {
        return $this->hasMany(Order::class, 'waiter_id');
    }

    /**
     * Get payments processed by this user.
     */
    public function processedPayments()
    {
        return $this->hasMany(Payment::class, 'processed_by');
    }

    /**
     * Scope to get only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get users by role.
     */
    public function scopeWithRole($query, $role)
    {
        return $query->role($role);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is cashier.
     */
    public function isCashier(): bool
    {
        return $this->hasRole('cashier');
    }

    /**
     * Check if user is waiter.
     */
    public function isWaiter(): bool
    {
        return $this->hasRole('waiter');
    }

    /**
     * Check if user is kitchen staff.
     */
    public function isKitchen(): bool
    {
        return $this->hasRole('kitchen');
    }

    /**
     * Check if user is supervisor.
     */
    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
    }

    /**
     * Get the dynamic PIN that changes every 5 minutes.
     * This PIN is calculated based on current time + user ID, no scheduler needed!
     */
    public function getDynamicPinAttribute(): ?string
    {
        // Only supervisors have PINs
        if (!$this->hasRole('supervisor')) {
            return null;
        }

        // Get the current 5-minute time slot (changes every 5 minutes)
        $timeSlot = floor(time() / 300); // 300 seconds = 5 minutes

        // Create a unique seed using user ID and time slot
        $seed = $this->id + $timeSlot;

        // Use the seed to generate a deterministic "random" PIN
        mt_srand($seed);
        $pin = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        // Reset the random seed to avoid affecting other random operations
        mt_srand();

        return $pin;
    }
}
