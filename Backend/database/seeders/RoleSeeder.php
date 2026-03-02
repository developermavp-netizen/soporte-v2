<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   // database/seeders/RoleSeeder.php
public function run()
{
    $roles = [
        ['name' => 'ADMIN', 'description' => 'Acceso total al sistema'],
        ['name' => 'TECNICO', 'description' => 'Puede crear órdenes, cerrarlas y agregar notas'],
        ['name' => 'VENTAS', 'description' => 'Solo puede crear órdenes y ver el progreso'],
    ];
    
    foreach ($roles as $role) {
        \App\Models\Role::create($role);
    }
}
}
