<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'amount', 'payment_method',
        'reference', 'received_by', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}