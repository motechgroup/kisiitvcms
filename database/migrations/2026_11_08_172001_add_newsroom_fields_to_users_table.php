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
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('password');
            $table->string('job_title')->nullable()->after('avatar_path');
            $table->text('bio')->nullable()->after('job_title');
            $table->string('phone')->nullable()->after('bio');
            $table->boolean('is_active')->default(true)->index()->after('phone');
            $table->json('social_links')->nullable()->after('is_active');
            $table->timestamp('deactivated_at')->nullable()->after('social_links');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_path',
                'job_title',
                'bio',
                'phone',
                'is_active',
                'social_links',
                'deactivated_at',
            ]);
        });
    }
};
