<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name', 'last_name', 'phone', 'alternative_phone',
        'email', 'address', 'notes'
    ];

    // Accesor para nombre completo
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Un cliente tiene muchas órdenes
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}