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
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('folio')->unique();
        $table->foreignId('customer_id')->constrained();
        $table->foreignId('device_id')->constrained();
        $table->foreignId('status_id')->constrained();
        $table->text('issue_reported');
        $table->text('technical_notes')->nullable();
        $table->decimal('estimated_cost', 10, 2)->nullable();
        $table->integer('estimated_days')->nullable();
        $table->timestamp('promised_date')->nullable();
        $table->string('created_by');
        $table->string('assigned_to')->nullable();
        $table->timestamps();
        
        $table->index('folio');
        $table->index('customer_id');
        $table->index('status_id');
        $table->index('created_at');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
