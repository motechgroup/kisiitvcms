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
        Schema::table('event_ticket_orders', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('event_ticket_type_id')->constrained('users')->nullOnDelete();
            $table->string('buyer_email')->nullable()->change();
            $table->string('buyer_phone', 50)->nullable()->change();
        });

        Schema::table('event_tickets', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('event_ticket_type_id')->constrained('users')->nullOnDelete();
            $table->string('attendee_email')->nullable()->change();
            $table->string('attendee_phone', 50)->nullable()->change();
        });

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->index('phone');
            });
        } catch (Throwable) {
            // Index already exists on some setups.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('event_ticket_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
