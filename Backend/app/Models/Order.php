<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'folio', 'customer_id', 'device_id', 'status_id',
        'issue_reported', 'technical_notes', 'estimated_cost',
        'estimated_days', 'promised_date', 'created_by', 'assigned_to'
    ];

    protected $casts = [
        'promised_date' => 'datetime',
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
    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function notes()
    {
        return $this->hasMany(OrderNote::class);
    }

    public function repairs()
    {
        return $this->hasMany(Repair::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    // Obtener último pago
    public function lastPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    // Obtener última entrega
    public function lastDelivery()
    {
        return $this->hasOne(Delivery::class)->latestOfMany();
    }
}