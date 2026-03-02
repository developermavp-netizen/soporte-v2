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
    Schema::create('customers', function (Blueprint $table) {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
        $table->string('phone');
        $table->string('alternative_phone')->nullable();
        $table->string('email')->nullable();
        $table->text('address')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps(); // created_at, updated_at
        
        $table->unique(['phone', 'email']);
        $table->index('phone');
        $table->index('email');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
