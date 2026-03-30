<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'vat',
        'sscl',
        'vat_reg_no',
    ];

    protected $casts = [
        'vat' => 'decimal:2',
        'sscl' => 'decimal:2',
    ];
}
