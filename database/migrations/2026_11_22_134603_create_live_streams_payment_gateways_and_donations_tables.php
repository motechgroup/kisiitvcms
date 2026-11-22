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
        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('platform', 30)->default('youtube')->index();
            $table->string('stream_url', 500);
            $table->string('embed_url', 500)->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->string('status', 30)->default('live')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('donations_enabled')->default(true)->index();
            $table->decimal('donation_goal', 12, 2)->nullable();
            $table->string('donation_call_to_action')->nullable();
            $table->unsignedInteger('viewer_count')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->string('provider_label')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->boolean('is_test_mode')->default(true);
            $table->string('currency', 10)->default('KES');
            $table->text('public_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->text('passkey_or_webhook_secret')->nullable();
            $table->string('shortcode_or_merchant_id')->nullable();
            $table->string('account_reference')->nullable();
            $table->text('instructions')->nullable();
            $table->integer('display_order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_stream_id')->nullable()->constrained('live_streams')->nullOnDelete();
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
            $table->string('gateway_code', 50)->index();
            $table->string('transaction_reference', 100)->unique();
            $table->string('external_reference', 150)->nullable()->index();
            $table->string('donor_name');
            $table->string('donor_email')->nullable();
            $table->string('donor_phone', 50)->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('KES');
            $table->text('message')->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('status', 30)->default('pending')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
        Schema::dropIfExists('payment_gateways');
        Schema::dropIfExists('live_streams');
    }
};
