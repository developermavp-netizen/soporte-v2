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
    Schema::create('repairs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id')->constrained()->onDelete('cascade');
        $table->text('description');
        $table->decimal('cost', 10, 2)->nullable();
        $table->decimal('price', 10, 2)->nullable();
        $table->integer('warranty_days')->default(30);
        $table->boolean('is_completed')->default(false);
        $table->timestamp('completed_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
