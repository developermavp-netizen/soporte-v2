<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // database/migrations/xxxx_add_role_id_to_users_table.php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->foreignId('role_id')->nullable()->constrained();
        $table->string('username')->unique()->after('name');
        $table->string('phone')->nullable()->after('email');
        $table->boolean('is_active')->default(true)->after('role_id');
        $table->timestamp('last_login')->nullable()->after('is_active');
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropForeign(['role_id']);
        $table->dropColumn(['role_id', 'username', 'phone', 'is_active', 'last_login']);
    });
}
};
