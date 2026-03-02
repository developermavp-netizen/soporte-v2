<?php

namespace App\Models;

// IMPORTANTE: Agregar estos use
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;  // ← Este es el que falta

class User extends Authenticatable
{
    // Agregar HasApiTokens aquí
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'phone',
        'role_id', 'is_active', 'last_login', 'avatar', 'cloudinary_avatar_id'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'last_login' => 'datetime',
    ];

    // Relaciones
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Helper methods
    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function isAdmin()
    {
        return $this->hasRole('ADMIN');
    }

    public function isTecnico()
    {
        return $this->hasRole('TECNICO');
    }

    public function isVentas()
    {
        return $this->hasRole('VENTAS');
    }
}