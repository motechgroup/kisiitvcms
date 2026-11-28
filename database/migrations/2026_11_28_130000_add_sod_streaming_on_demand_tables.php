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
        Schema::table('live_streams', function (Blueprint $table) {
            $table->boolean('is_sod')->default(false)->index()->after('is_featured');
            $table->decimal('sod_price', 12, 2)->default(0)->after('is_sod');
            $table->string('sod_currency', 10)->default('KES')->after('sod_price');
            $table->boolean('serve_on_main_live')->default(true)->index()->after('sod_currency');
        });

        Schema::create('stream_access_passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->constrained('live_streams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
            $table->string('gateway_code', 50)->index();
            $table->string('order_reference', 100)->unique();
            $table->string('external_reference', 150)->nullable()->index();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_email')->nullable();
            $table->string('buyer_phone', 50)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('KES');
            $table->string('status', 20)->default('pending')->index();
            $table->string('access_token', 64)->unique();
            $table->string('device_fingerprint_hash', 64)->nullable()->index();
            $table->string('active_session_id', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stream_access_passes');

        Schema::table('live_streams', function (Blueprint $table) {
            $table->dropColumn(['is_sod', 'sod_price', 'sod_currency', 'serve_on_main_live']);
        });
    }
};
