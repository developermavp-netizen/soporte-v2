<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // Relación: Un tipo de dispositivo tiene muchas marcas
    public function brands()
    {
        return $this->hasMany(Brand::class);
    }
}