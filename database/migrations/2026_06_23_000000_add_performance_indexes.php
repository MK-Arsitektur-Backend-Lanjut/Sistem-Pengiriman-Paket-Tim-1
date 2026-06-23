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
        Schema::table('packages', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('package_histories', function (Blueprint $table) {
            $table->index(['package_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('package_histories', function (Blueprint $table) {
            $table->dropIndex(['package_id', 'recorded_at']);
        });
    }
};
