<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('devices', function (Blueprint $table) {
        $table->id();
        $table->foreignId('device_type_id')->constrained();
        $table->foreignId('brand_id')->constrained();
        $table->string('model');
        $table->string('serial_number')->unique()->nullable();
        $table->string('password')->nullable();
        $table->text('accessories')->nullable();
        $table->text('physical_condition')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
        
        $table->index('serial_number');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
