<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'printer_name',
        'printer_ip',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the items for this station.
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Get the KOTs for this station.
     */
    public function kots(): HasMany
    {
        return $this->hasMany(Kot::class);
    }

    /**
     * Scope to get only active stations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if printer is configured.
     */
    public function hasPrinter(): bool
    {
        return !empty($this->printer_name) || !empty($this->printer_ip);
    }
}
