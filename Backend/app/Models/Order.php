<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'folio', 'customer_id', 'device_id', 'status_id',
        'issue_reported', 'technical_notes', 'estimated_cost',
        'estimated_days', 'promised_date', 'created_by', 'assigned_to'
    ];

    protected $casts = [
        'promised_date'  => 'datetime',
        'estimated_cost' => 'decimal:2'
    ];

    // Relaciones principales
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    // Relaciones con tablas hijas
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class);
    }

    public function repairs(): HasMany
    {
        return $this->hasMany(Repair::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    // Último pago / última entrega
    public function lastPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function lastDelivery(): HasOne
    {
        return $this->hasOne(Delivery::class)->latestOfMany();
    }
}