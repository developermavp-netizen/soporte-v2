<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repair extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'description',
        'part_model',
        'supplier',
        'quantity',
        'cost',
        'price',
        'type',
        'warranty_days',
        'is_completed',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'cost'         => 'decimal:2',
        'price'        => 'decimal:2',
        'quantity'     => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}