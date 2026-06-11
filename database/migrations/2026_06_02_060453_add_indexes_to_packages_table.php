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
            $table->index('package_status');
            $table->index('sender_name');
            $table->index('receiver_name');
            $table->index('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropIndex(['package_status']);
            $table->dropIndex(['sender_name']);
            $table->dropIndex(['receiver_name']);
            $table->dropIndex(['warehouse_id']);
        });
    }
};
