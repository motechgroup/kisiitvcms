<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\LiveStream;
use App\Models\PaymentGateway;
use App\Models\Role;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PaymentGatewaySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LiveStreamAndDonationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            CategorySeeder::class,
            SiteSettingSeeder::class,
            PaymentGatewaySeeder::class,
        ]);

        $this->superAdmin = User::factory()->create([
            'email' => 'admin@kisiitv.com',
            'name' => 'Broadcast Director',
            'is_active' => true,
        ]);

        $superAdminRole = Role::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $this->superAdmin->roles()->attach($superAdminRole->id);
        }

        Http::fake([
            'https://sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'test_daraja_token_abc',
                'expires_in' => '3599',
            ], 200),
            'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'MerchantRequestID' => '29115-34620561-1',
                'CheckoutRequestID' => 'ws_CO_191220191020363925',
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success. Request accepted for processing',
                'CustomerMessage' => 'Success. Request accepted for processing',
            ], 200),
        ]);
    }

    public function test_admin_can_create_youtube_twitch_and_facebook_live_streams_with_auto_resolved_embeds(): void
    {
        // 1. YouTube Live Stream
        $ytResponse = $this->actingAs($this->superAdmin)->post(route('admin.streams.store'), [
            'title' => 'Kisii TV YouTube Breaking Coverage',
            'platform' => 'youtube',
            'stream_url' => 'https://www.youtube.com/watch?v=jfKfPfyJRdk',
            'status' => 'live',
            'is_active' => 1,
            'is_featured' => 1,
            'donations_enabled' => 1,
            'donation_goal' => 75000,
        ]);

        $ytResponse->assertRedirect(route('admin.streams.index'));
        $ytStream = LiveStream::where('platform', 'youtube')->firstOrFail();
        $this->assertSame('https://www.youtube.com/embed/jfKfPfyJRdk?autoplay=1&mute=1&playsinline=1&rel=0', $ytStream->embed_url);
        $this->assertStringContainsString('autoplay=1&mute=1', $ytStream->getComputedEmbedUrl('localhost'));

        // Verify all Unlisted YouTube link formats resolve to the autoplay embed URL
        $unlistedUrls = [
            'https://youtu.be/AbCdEfGhIjK?si=unlistedShareToken123',
            'https://www.youtube.com/live/AbCdEfGhIjK?si=liveUnlistedToken456&feature=share',
            'https://studio.youtube.com/video/AbCdEfGhIjK/livestreaming',
            'https://www.youtube.com/watch?app=desktop&v=AbCdEfGhIjK&feature=youtu.be',
            '<iframe width="560" height="315" src="https://www.youtube.com/embed/AbCdEfGhIjK?si=embedToken789" frameborder="0" allowfullscreen></iframe>',
        ];

        foreach ($unlistedUrls as $unlistedUrl) {
            $this->assertSame(
                'https://www.youtube.com/embed/AbCdEfGhIjK?autoplay=1&mute=1&playsinline=1&rel=0',
                LiveStream::resolveEmbedUrl('youtube', $unlistedUrl)
            );
        }

        // 2. Twitch Live Stream (using only title + JavaScript Embed Code, no stream_url required)
        $twitchJsEmbed = <<<'HTML'
<div id="twitch-embed"></div>
<script src="https://player.twitch.tv/js/embed/v1.js"></script>
<script type="text/javascript">
  new Twitch.Player("twitch-embed", {
    channel: "kisiitv_official"
  });
</script>
HTML;

        $twitchResponse = $this->actingAs($this->superAdmin)->post(route('admin.streams.store'), [
            'title' => 'Kisii TV Twitch Townhall Stream',
            'platform' => 'twitch',
            'embed_url' => $twitchJsEmbed,
            'is_active' => 1,
            'donations_enabled' => 1,
        ]);

        $twitchResponse->assertRedirect(route('admin.streams.index'));
        $twitchStream = LiveStream::where('platform', 'twitch')->firstOrFail();
        $this->assertTrue($twitchStream->isTwitch());
        $this->assertSame('kisiitv_official', $twitchStream->getTwitchPlayerOptions('kisiitv.co.ke')['channel']);

        // Because only one stream at a time is served to public, activating Twitch stream automatically deactivated the previous YouTube stream
        $this->assertTrue($twitchStream->is_active);
        $this->assertFalse($ytStream->fresh()->is_active);

        // Verify public Twitch stream page renders Twitch.Player JavaScript SDK instead of an iframe
        $twitchPublicPage = $this->get(route('live.index'));
        $twitchPublicPage->assertStatus(200);
        $twitchPublicPage->assertSee('https://player.twitch.tv/js/embed/v1.js', false);
        $twitchPublicPage->assertSee("new Twitch.Player('kisiitv-twitch-player'", false);
        $twitchPublicPage->assertSee('kisiitv_official');

        // Admin can switch which single stream is served to the public via activate-public
        $switchResponse = $this->actingAs($this->superAdmin)->patch(route('admin.streams.activate-public', $ytStream));
        $switchResponse->assertRedirect(route('admin.streams.index'));
        $this->assertTrue($ytStream->fresh()->is_active);
        $this->assertFalse($twitchStream->fresh()->is_active);

        // 2b. Twitch Live Stream using HTML <iframe> code instead of JavaScript
        $twitchIframeCode = '<iframe src="https://player.twitch.tv/?channel=strogo&parent=www.example.com" frameborder="0" allowfullscreen="true" scrolling="no" height="378" width="620"></iframe>';
        $twitchIframeResp = $this->actingAs($this->superAdmin)->post(route('admin.streams.store'), [
            'title' => 'Kisii TV Twitch Iframe Stream',
            'platform' => 'twitch',
            'embed_url' => $twitchIframeCode,
            'is_active' => 1,
            'donations_enabled' => 1,
        ]);

        $twitchIframeResp->assertRedirect(route('admin.streams.index'));
        $twitchIframeStream = LiveStream::where('title', 'Kisii TV Twitch Iframe Stream')->firstOrFail();
        $this->assertTrue($twitchIframeStream->usesTwitchIframe());
        $this->assertFalse($twitchIframeStream->usesTwitchJsPlayer());
        $this->assertSame(
            'https://player.twitch.tv/?channel=strogo&parent=kisiitv.co.ke&autoplay=true',
            $twitchIframeStream->getComputedEmbedUrl('kisiitv.co.ke')
        );

        $twitchIframePublicPage = $this->get(route('live.index'));
        $twitchIframePublicPage->assertStatus(200);
        $twitchIframePublicPage->assertSee('id="kisiitv-live-iframe"', false);
        $twitchIframePublicPage->assertSee('https://player.twitch.tv/?channel=strogo&amp;parent=localhost&amp;autoplay=true', false);

        // 3. Facebook Live Stream
        $fbUrl = 'https://www.facebook.com/KisiitvAmandegere/videos/123456789012345';
        $fbResponse = $this->actingAs($this->superAdmin)->post(route('admin.streams.store'), [
            'title' => 'Kisii TV Facebook Live Broadcast',
            'platform' => 'facebook',
            'stream_url' => $fbUrl,
            'status' => 'live',
            'is_active' => 1,
            'donations_enabled' => 1,
        ]);

        $fbResponse->assertRedirect(route('admin.streams.index'));
        $fbStream = LiveStream::where('platform', 'facebook')->firstOrFail();
        $this->assertStringContainsString('https://www.facebook.com/plugins/video.php?href='.urlencode($fbUrl), $fbStream->embed_url);
        $this->assertStringContainsString('autoplay=true', $fbStream->embed_url);
    }

    public function test_admin_can_toggle_donations_per_live_stream_and_globally(): void
    {
        $stream = LiveStream::factory()->create([
            'title' => 'Devolution Debate Live',
            'donations_enabled' => true,
            'status' => 'live',
            'is_active' => true,
        ]);

        // Homepage does NOT show a donate button; donate is on the livestream page only
        $homePage = $this->get(route('home'));
        $homePage->assertStatus(200);
        $homePage->assertDontSee('Donate / Support');

        // Public livestream page shows the donate button and donation modal without exposing stream source badges, viewer counts, or donation targets
        $publicOn = $this->get(route('live.show', $stream->slug));
        $publicOn->assertStatus(200);
        $publicOn->assertSee('Donate / Support Stream');
        $publicOn->assertSee('name="live_stream_id"', false);
        $publicOn->assertSee('Share on WhatsApp');
        $publicOn->assertSee('Share on Facebook');
        $publicOn->assertSee('Share on X');
        $publicOn->assertDontSee('YouTube Live');
        $publicOn->assertDontSee('Open on Youtube');
        $publicOn->assertDontSee('Platform:');
        $publicOn->assertDontSee('Goal: KES');
        $publicOn->assertDontSee('viewers');

        // Admin disables donations for this specific stream
        $toggleResp = $this->actingAs($this->superAdmin)
            ->patch(route('admin.streams.toggle-donations', $stream));
        $toggleResp->assertRedirect(route('admin.streams.index'));

        $stream->refresh();
        $this->assertFalse($stream->donations_enabled);

        // Public page hides donation box when disabled for the stream
        $publicOff = $this->get(route('live.show', $stream->slug));
        $publicOff->assertStatus(200);
        $publicOff->assertDontSee('name="live_stream_id"', false);

        // Submitting a donation to a stream with donations disabled is rejected
        $donateAttempt = $this->post(route('donations.store'), [
            'live_stream_id' => $stream->id,
            'gateway_code' => 'mpesa',
            'amount' => 500,
            'donor_name' => 'John Omwenga',
            'donor_phone' => '254712345678',
        ]);
        $donateAttempt->assertSessionHas('error', 'Donations are currently disabled for this live stream.');
        $this->assertDatabaseCount('donations', 0);

        // Re-enable stream donations, then toggle global donations off
        $this->actingAs($this->superAdmin)->patch(route('admin.streams.toggle-donations', $stream));
        $this->actingAs($this->superAdmin)->patch(route('admin.streams.toggle-global-donations'));

        $this->assertFalse((bool) SiteSetting::get('live_donations_enabled'));
    }

    public function test_admin_can_manage_mpesa_paystack_paypal_and_flutterwave_payment_gateways(): void
    {
        $indexResponse = $this->actingAs($this->superAdmin)->get(route('admin.payment-gateways.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('M-Pesa');
        $indexResponse->assertSee('Paystack');
        $indexResponse->assertSee('PayPal');
        $indexResponse->assertSee('Flutterwave');
        $indexResponse->assertSee('showPublic');
        $indexResponse->assertSee('showSecret');
        $indexResponse->assertSee('showPasskey');
        $indexResponse->assertSee('https://lindrapp.top/api/v1/webhooks/mpesa');

        $mpesa = PaymentGateway::where('code', 'mpesa')->firstOrFail();
        $this->assertSame('9a7lKfNJxw0eIiCay8OqFHeBGNXSfBLQTxWRACneeKEfSBjD', $mpesa->public_key);
        $this->assertSame('174379', $mpesa->shortcode_or_merchant_id);
        $this->assertSame('https://lindrapp.top/api/v1/webhooks/mpesa', $mpesa->callback_url);

        $updateResponse = $this->actingAs($this->superAdmin)->put(route('admin.payment-gateways.update', $mpesa), [
            'name' => 'M-Pesa Daraja Express',
            'provider_label' => 'Lipa na M-Pesa',
            'currency' => 'KES',
            'shortcode_or_merchant_id' => '522522',
            'account_reference' => 'KISIITV-LIVE',
            'public_key' => 'daraja_consumer_key_123',
            'secret_key' => 'daraja_consumer_secret_456',
            'passkey_or_webhook_secret' => 'daraja_passkey_789',
            'instructions' => 'Use Paybill 522522 Account KISIITV-LIVE',
            'is_active' => 1,
            'is_test_mode' => 0,
        ]);

        $updateResponse->assertRedirect(route('admin.payment-gateways.index'));

        $mpesa->refresh();
        $this->assertSame('522522', $mpesa->shortcode_or_merchant_id);
        $this->assertSame('KISIITV-LIVE', $mpesa->account_reference);
        $this->assertFalse($mpesa->is_test_mode);

        // Toggle active status off
        $this->actingAs($this->superAdmin)->patch(route('admin.payment-gateways.toggle-active', $mpesa));
        $this->assertFalse($mpesa->fresh()->is_active);
    }

    #[TestWith(['mpesa'])]
    #[TestWith(['paystack'])]
    #[TestWith(['paypal'])]
    #[TestWith(['flutterwave'])]
    public function test_viewer_can_donate_to_live_stream_using_each_supported_payment_gateway(string $gatewayCode): void
    {
        $stream = LiveStream::factory()->create([
            'donations_enabled' => true,
            'donation_goal' => 10000,
        ]);

        $response = $this->post(route('donations.store'), [
            'live_stream_id' => $stream->id,
            'gateway_code' => $gatewayCode,
            'amount' => 1500,
            'donor_name' => 'Dr. Bosibori Nyangau',
            'donor_email' => 'bosibori@example.com',
            'donor_phone' => '254711223344',
            'message' => 'Keep up the great live coverage!',
        ]);

        $donation = Donation::where('gateway_code', $gatewayCode)->firstOrFail();
        $response->assertRedirect(route('donations.checkout', $donation->transaction_reference));
        $this->assertSame('pending', $donation->status);

        // Complete the checkout
        $completeResponse = $this->post(route('donations.complete', $donation->transaction_reference), [
            'external_reference' => strtoupper($gatewayCode).'-TX-998877',
        ]);

        $completeResponse->assertRedirect(route('live.show', $stream->slug));
        $donation->refresh();
        $this->assertSame('completed', $donation->status);
        $this->assertSame(strtoupper($gatewayCode).'-TX-998877', $donation->external_reference);
        $this->assertNotNull($donation->paid_at);
    }

    public function test_homepage_and_navigation_display_donate_buttons(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);
        $response->assertSee('Donate');
        $response->assertSee('Support Kisii TV');
        $response->assertSee(route('donations.index'));
        $response->assertSee('open-donate-modal');
        $response->assertSee('Help Kisii TV Keep In-Depth County Investigations &amp; Live News Free For All', false);
    }

    public function test_viewer_can_view_donation_portal_and_make_general_donation(): void
    {
        $portal = $this->get(route('donations.index'));
        $portal->assertStatus(200);
        $portal->assertSee('Support Kisii TV Newsroom');
        $portal->assertSee('mpesa.jpg');
        $portal->assertSee('paystack.png');

        // Initiate general donation (without live_stream_id)
        $store = $this->post(route('donations.store'), [
            'gateway_code' => 'mpesa',
            'amount' => 1000,
            'donor_name' => 'Mama Moraa',
            'donor_email' => 'moraa@example.com',
            'donor_phone' => '0712345678',
            'message' => 'Keep telling our stories!',
        ]);

        $donation = Donation::where('donor_name', 'Mama Moraa')->firstOrFail();
        $this->assertNull($donation->live_stream_id);
        $store->assertRedirect(route('donations.checkout', $donation->transaction_reference));

        // Checkout page displays general checkout
        $checkout = $this->get(route('donations.checkout', $donation->transaction_reference));
        $checkout->assertStatus(200);
        $checkout->assertSee('Check Your Phone');
        $checkout->assertSee('1,000.00');

        // Complete general checkout
        $complete = $this->post(route('donations.complete', $donation->transaction_reference), [
            'external_reference' => 'QA8819283K',
        ]);
        $complete->assertRedirect(route('donations.index'));
        $this->assertSame('completed', $donation->fresh()->status);
    }

    public function test_admin_can_create_campaign_and_share_donation_link(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.campaigns.store'), [
            'title' => 'County Investigative Unit 2027 Equipment Fund',
            'target_amount' => 350000,
            'currency' => 'KES',
            'summary' => 'Equipping undercover reporters with reliable 4K gear.',
            'description' => 'Detailed narrative about the need for independent community reporting.',
            'is_active' => 1,
            'is_featured' => 1,
        ]);

        $response->assertRedirect(route('admin.campaigns.index'));
        $campaign = DonationCampaign::where('title', 'County Investigative Unit 2027 Equipment Fund')->firstOrFail();
        $this->assertSame(350000.0, (float) $campaign->target_amount);
        $this->assertTrue($campaign->is_active);
        $this->assertTrue($campaign->is_featured);

        // Verify share URL
        $this->assertSame(route('campaigns.show', $campaign->slug), $campaign->shareUrl());

        // Admin index lists the campaign and share link
        $index = $this->actingAs($this->superAdmin)->get(route('admin.campaigns.index'));
        $index->assertStatus(200);
        $index->assertSee('County Investigative Unit 2027 Equipment Fund');
        $index->assertSee($campaign->shareUrl());
    }

    public function test_donor_can_visit_campaign_link_and_donate(): void
    {
        $campaign = DonationCampaign::factory()->create([
            'title' => 'Mobile Broadcast Van Project',
            'target_amount' => 500000,
            'is_active' => true,
        ]);

        // Public campaign page loads with progress bar & share link
        $page = $this->get(route('campaigns.show', $campaign->slug));
        $page->assertStatus(200);
        $page->assertSee('Mobile Broadcast Van Project');
        $page->assertSee('Copy Direct Link');
        $page->assertSee('WhatsApp');

        // Donate to this campaign
        $donate = $this->post(route('donations.store'), [
            'campaign_id' => $campaign->id,
            'gateway_code' => 'mpesa',
            'amount' => 5000,
            'donor_name' => 'Eng. Zachary Onyancha',
            'donor_phone' => '0722001122',
            'donor_email' => 'onyancha@example.com',
            'message' => 'Proud to support our home station!',
        ]);

        $donation = Donation::where('donor_name', 'Eng. Zachary Onyancha')->firstOrFail();
        $this->assertSame($campaign->id, $donation->campaign_id);
        $donate->assertRedirect(route('donations.checkout', $donation->transaction_reference));

        // Checkout page shows campaign attribution
        $checkout = $this->get(route('donations.checkout', $donation->transaction_reference));
        $checkout->assertStatus(200);
        $checkout->assertSee('Campaign:');
        $checkout->assertSee('Mobile Broadcast Van Project');

        // Complete payment
        $complete = $this->post(route('donations.complete', $donation->transaction_reference), [
            'external_reference' => 'MPESA-XYZ999',
        ]);
        $complete->assertRedirect(route('campaigns.show', $campaign->slug));

        // Campaign stats update
        $campaign->refresh();
        $this->assertSame(5000.0, $campaign->totalRaised());
        $this->assertSame(1, $campaign->donorCount());
        $this->assertSame(1, $campaign->progressPercentage());
    }

    public function test_mpesa_stk_push_and_status_polling_endpoints(): void
    {
        $donation = Donation::factory()->create([
            'gateway_code' => 'mpesa',
            'amount' => 500,
            'donor_phone' => '0712345678',
            'status' => 'pending',
        ]);

        // STK push trigger
        $stkResponse = $this->postJson(route('donations.stk-push', $donation->transaction_reference), [
            'phone' => '0712345678',
        ]);
        $stkResponse->assertStatus(200);
        $stkResponse->assertJson(['success' => true]);

        // Status polling
        $statusResponse = $this->getJson(route('donations.status', $donation->transaction_reference));
        $statusResponse->assertStatus(200);
        $statusResponse->assertJson([
            'status' => 'pending',
            'is_completed' => false,
        ]);
    }

    public function test_payment_webhook_listener_marks_donation_completed(): void
    {
        $donation = Donation::factory()->create([
            'gateway_code' => 'paystack',
            'amount' => 1200,
            'status' => 'pending',
        ]);

        $webhook = $this->postJson(route('donations.webhook', 'paystack'), [
            'event' => 'charge.success',
            'data' => [
                'reference' => $donation->transaction_reference,
                'status' => 'success',
            ],
        ]);

        $webhook->assertStatus(200);
        $donation->refresh();
        $this->assertSame('completed', $donation->status);
        $this->assertSame($donation->transaction_reference, $donation->external_reference);
    }

    public function test_mpesa_donation_requires_only_amount_and_phone_and_auto_triggers_stk_push(): void
    {
        $response = $this->post(route('donations.store'), [
            'gateway_code' => 'mpesa',
            'amount' => 250,
            'donor_phone' => '0712345678',
        ]);

        $donation = Donation::where('donor_phone', '0712345678')->firstOrFail();
        $this->assertSame('Kisii TV Supporter', $donation->donor_name);
        $this->assertNull($donation->donor_email);
        $this->assertNotNull($donation->metadata['checkout_request_id'] ?? null);
        $response->assertRedirect(route('donations.checkout', $donation->transaction_reference));
        $response->assertSessionHas('stk_result');
    }

    public function test_paystack_and_flutterwave_redirect_to_real_hosted_checkout_when_configured(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/test-session-123',
                ],
            ], 200),
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                ],
            ], 200),
            'https://api.flutterwave.com/v3/payments' => Http::response([
                'status' => 'success',
                'data' => [
                    'link' => 'https://checkout.flutterwave.com/v3/hosted/pay/test-flw-456',
                ],
            ], 200),
            'https://api.flutterwave.com/v3/transactions/*/verify' => Http::response([
                'status' => 'success',
                'data' => [
                    'status' => 'successful',
                ],
            ], 200),
        ]);

        $paystack = PaymentGateway::where('code', 'paystack')->firstOrFail();
        $paystack->update(['secret_key' => 'sk_test_paystack_123']);

        $paystackResp = $this->post(route('donations.store'), [
            'gateway_code' => 'paystack',
            'amount' => 1000,
        ]);
        $paystackResp->assertRedirect('https://checkout.paystack.com/test-session-123');

        $paystackDonation = Donation::where('gateway_code', 'paystack')->latest()->firstOrFail();
        $callbackResp = $this->get(route('donations.callback', [
            'gateway' => 'paystack',
            'reference' => $paystackDonation->transaction_reference,
        ]));
        $callbackResp->assertRedirect(route('donations.index'));
        $this->assertSame('completed', $paystackDonation->fresh()->status);

        $flutterwave = PaymentGateway::where('code', 'flutterwave')->firstOrFail();
        $flutterwave->update(['secret_key' => 'FLWSECK_TEST-123']);

        $flwResp = $this->post(route('donations.store'), [
            'gateway_code' => 'flutterwave',
            'amount' => 2000,
        ]);
        $flwResp->assertRedirect('https://checkout.flutterwave.com/v3/hosted/pay/test-flw-456');

        $flwDonation = Donation::where('gateway_code', 'flutterwave')->latest()->firstOrFail();
        $flwCallback = $this->get(route('donations.callback', [
            'gateway' => 'flutterwave',
            'tx_ref' => $flwDonation->transaction_reference,
            'transaction_id' => '99887766',
            'status' => 'successful',
        ]));
        $flwCallback->assertRedirect(route('donations.index'));
        $this->assertSame('completed', $flwDonation->fresh()->status);
        $this->assertSame('99887766', $flwDonation->fresh()->external_reference);
    }
}
