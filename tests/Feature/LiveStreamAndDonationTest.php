<?php

namespace Tests\Feature;

use App\Models\Donation;
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
        $publicOn->assertSee('Proceed to Donate');
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
        $publicOff->assertDontSee('Proceed to Donate');

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

        $mpesa = PaymentGateway::where('code', 'mpesa')->firstOrFail();

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
}
