<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'device_type_id'];

    // Relación: Una marca pertenece a un tipo de dispositivo
    public function deviceType()
    {
        return $this->belongsTo(DeviceType::class);
    }

    // Relación: Una marca tiene muchos dispositivos
    public function devices()
    {
        return $this->hasMany(Device::class);
    }
}