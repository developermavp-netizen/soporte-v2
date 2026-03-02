<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
  public function run()
{
    $adminRole = \App\Models\Role::where('name', 'ADMIN')->first();
    
    \App\Models\User::create([
        'name' => 'Administrador',
        'username' => 'admin',
        'email' => 'admin@soporte.com',
        'password' => bcrypt('admin123'),
        'role_id' => $adminRole->id,
        'is_active' => true,
    ]);
}
}
