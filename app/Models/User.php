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
        'email',
        'password',
        'phone',
        'employee_id',
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
            'email_verified_at' => 'datetime',
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
     * Get audit logs.
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
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
}
