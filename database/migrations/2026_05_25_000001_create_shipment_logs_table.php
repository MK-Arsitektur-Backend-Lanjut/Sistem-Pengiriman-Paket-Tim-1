<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modul 2: Tracking System (Core)
     *
     * Tabel ini menggantikan `shipments` + `tracking_histories`.
     * Setiap baris = satu kejadian/event dalam perjalanan paket.
     *
     * Requirement: minimal 25.000 data log tracking (seeder).
     */
    public function up(): void
    {
        Schema::create('shipment_logs', function (Blueprint $table) {
            $table->id();

            // ── M1 Integration: Paket yang sedang dilacak ──
            $table->foreignId('package_id')
                ->constrained('packages')
                ->cascadeOnDelete();

            // ── Status kronologis perjalanan paket ──
            $table->enum('status', [
                'registered',       // Paket baru terdaftar di gudang
                'picked_up',        // Dijemput armada dari gudang
                'in_transit',       // Dalam perjalanan antar hub
                'arrived_at_hub',   // Tiba di hub transit
                'out_for_delivery', // Keluar untuk antar ke penerima
                'delivered',        // Terkirim ke penerima
                'failed',           // Gagal kirim
                'returned',         // Dikembalikan ke pengirim
            ])->index();

            // ── M4 Integration: Hub tempat kejadian ini terjadi ──
            $table->foreignId('hub_id')
                ->nullable()
                ->constrained('hubs')
                ->nullOnDelete();

            // ── M4 Integration: Armada yang mengantar (jika ada) ──
            $table->foreignId('fleet_id')
                ->nullable()
                ->constrained('fleets')
                ->nullOnDelete();

            // ── Informasi lokasi & catatan ──
            $table->string('location_note')->nullable(); // deskripsi lokasi bebas
            $table->text('notes')->nullable();           // catatan tambahan

            // ── Waktu kejadian (bisa di-set manual, berbeda dari created_at) ──
            $table->timestamp('recorded_at')->useCurrent()->index();

            // ── M3 Integration: Petugas yang mencatat (opsional) ──
            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // ── Index untuk performa query ──
            $table->index(['package_id', 'status']);
            $table->index(['package_id', 'recorded_at']);
            $table->index(['hub_id', 'status']);
            $table->index(['fleet_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_logs');
    }
};
