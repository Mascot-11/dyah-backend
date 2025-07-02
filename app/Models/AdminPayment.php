<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'customer_type',
        'payment_date',
        'payment_time',
        'payment_method',
        'amount',
    ];
}

