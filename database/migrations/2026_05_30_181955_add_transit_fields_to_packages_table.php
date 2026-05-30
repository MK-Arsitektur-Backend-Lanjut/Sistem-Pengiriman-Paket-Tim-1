<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->foreignId('fleet_id')->nullable()->constrained('fleets')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropForeign(['hub_id']);
            $table->dropColumn('hub_id');
            $table->dropForeign(['fleet_id']);
            $table->dropColumn('fleet_id');
        });
    }
};
