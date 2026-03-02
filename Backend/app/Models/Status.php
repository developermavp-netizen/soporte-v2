<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'color', 'sort_order'];

    // Un estado tiene muchas órdenes
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Un estado tiene muchos historiales
    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }
}