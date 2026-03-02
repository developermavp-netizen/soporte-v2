<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repair extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'description', 'cost', 'price',
        'warranty_days', 'is_completed', 'completed_at'
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'cost' => 'decimal:2',
        'price' => 'decimal:2'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}