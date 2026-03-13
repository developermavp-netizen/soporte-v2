<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('brands', function (Blueprint $table) {
        // Eliminar el índice único simple
        $table->dropUnique('brands_name_unique');

        // Crear índice único compuesto (name + device_type_id)
        $table->unique(['name', 'device_type_id']);
    });
}

public function down(): void
{
    Schema::table('brands', function (Blueprint $table) {
        $table->dropUnique(['name', 'device_type_id']);
        $table->unique('name');
    });
}
};
