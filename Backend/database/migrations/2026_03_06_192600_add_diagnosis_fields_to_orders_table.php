<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('diagnosis')->nullable()->after('issue_reported');
            $table->text('follow_up')->nullable()->after('diagnosis');
            $table->text('solution')->nullable()->after('follow_up');
            $table->json('diagnosis_images')->nullable()->after('solution');
            $table->json('follow_up_images')->nullable()->after('diagnosis_images');
            $table->json('solution_images')->nullable()->after('follow_up_images');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'diagnosis',
                'follow_up',
                'solution',
                'diagnosis_images',
                'follow_up_images',
                'solution_images'
            ]);
        });
    }
};