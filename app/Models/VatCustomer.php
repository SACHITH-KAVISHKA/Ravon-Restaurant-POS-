<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VatCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'vat_number',
        'telephone',
        'address',
    ];
}
