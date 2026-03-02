<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Status;

class StatusSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            ['code' => 'ABIERTO', 'name' => 'Abierto', 'color' => '#3498db', 'sort_order' => 1],
            ['code' => 'EN_PROCESO', 'name' => 'En proceso', 'color' => '#f39c12', 'sort_order' => 2],
            ['code' => 'TERMINADO', 'name' => 'Terminado', 'color' => '#27ae60', 'sort_order' => 3],
            ['code' => 'CANCELADO', 'name' => 'Cancelado', 'color' => '#e74c3c', 'sort_order' => 4],
        ];

        foreach ($statuses as $status) {
            Status::create($status);
        }
    }
}