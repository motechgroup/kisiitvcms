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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('venue');
            $table->string('city')->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable();
            $table->string('currency', 10)->default('KES');
            $table->boolean('is_published')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('event_ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->string('description', 500)->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedInteger('quantity_available')->nullable();
            $table->unsignedInteger('quantity_sold')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('event_ticket_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('event_ticket_type_id')->constrained('event_ticket_types')->cascadeOnDelete();
            $table->foreignId('payment_gateway_id')->nullable()->constrained('payment_gateways')->nullOnDelete();
            $table->string('gateway_code', 50)->default('free')->index();
            $table->string('order_reference', 100)->unique();
            $table->string('external_reference', 150)->nullable()->index();
            $table->string('buyer_name');
            $table->string('buyer_email');
            $table->string('buyer_phone', 50);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('KES');
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('sms_sent_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('event_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_ticket_order_id')->constrained('event_ticket_orders')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('event_ticket_type_id')->constrained('event_ticket_types')->cascadeOnDelete();
            $table->string('ticket_code', 40)->unique();
            $table->string('access_token', 64)->unique();
            $table->string('attendee_name');
            $table->string('attendee_email');
            $table->string('attendee_phone', 50);
            $table->string('status', 20)->default('valid')->index();
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_tickets');
        Schema::dropIfExists('event_ticket_orders');
        Schema::dropIfExists('event_ticket_types');
        Schema::dropIfExists('events');
    }
};
