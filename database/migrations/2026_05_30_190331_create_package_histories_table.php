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
        Schema::create('package_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->string('status');
            $table->foreignId('hub_id')->nullable()->constrained('hubs')->nullOnDelete();
            $table->foreignId('fleet_id')->nullable()->constrained('fleets')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_histories');
    }
};
