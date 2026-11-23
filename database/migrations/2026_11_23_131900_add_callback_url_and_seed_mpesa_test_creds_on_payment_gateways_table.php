<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->string('callback_url', 500)->nullable()->after('account_reference');
        });

        DB::table('payment_gateways')
            ->where('code', 'mpesa')
            ->update([
                'public_key' => '9a7lKfNJxw0eIiCay8OqFHeBGNXSfBLQTxWRACneeKEfSBjD',
                'secret_key' => 'GdtbnHA4Wl7uzyB4bNNlDPMP1B2fmSv3AvynXqvmIkoXexMQn3azKN3SVnczPSS8',
                'shortcode_or_merchant_id' => '174379',
                'passkey_or_webhook_secret' => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
                'callback_url' => 'https://lindrapp.top/api/v1/webhooks/mpesa',
                'is_test_mode' => true,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropColumn('callback_url');
        });
    }
};
