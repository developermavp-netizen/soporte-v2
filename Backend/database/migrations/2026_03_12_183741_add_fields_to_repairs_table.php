<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->string('part_model')->nullable()->after('description');
            $table->string('supplier')->nullable()->after('part_model');
            $table->unsignedInteger('quantity')->default(1)->after('supplier');
            $table->enum('type', ['ORIGINAL', 'GENERICO'])->default('ORIGINAL')->after('quantity');
            $table->string('created_by')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('repairs', function (Blueprint $table) {
            $table->dropColumn(['part_model', 'supplier', 'quantity', 'type', 'created_by']);
        });
    }
};