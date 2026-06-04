<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->index('status');
            $table->index('type');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('hubs', function (Blueprint $table) {
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['type']);
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('hubs', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
