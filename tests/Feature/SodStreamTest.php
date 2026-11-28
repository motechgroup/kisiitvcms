<?php

namespace Tests\Feature;

use App\Models\LiveStream;
use App\Models\PaymentGateway;
use App\Models\Role;
use App\Models\StreamAccessPass;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\PaymentGatewaySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SodStreamTest extends TestCase
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

    public function test_admin_can_create_unlisted_sod_stream_without_replacing_main_live_stream(): void
    {
        $mainLive = LiveStream::factory()->create([
            'title' => 'Kisii TV Main Public Broadcast',
            'platform' => 'youtube',
            'stream_url' => 'https://www.youtube.com/watch?v=mainLive123',
            'embed_url' => 'https://www.youtube.com/embed/mainLive123',
            'status' => 'live',
            'is_active' => true,
            'is_sod' => false,
            'serve_on_main_live' => true,
            'donations_enabled' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.streams.store'), [
            'title' => 'Gusii Live Music Concert VIP Stream',
            'platform' => 'youtube',
            'stream_url' => 'https://www.youtube.com/watch?v=concertVip9',
            'status' => 'live',
            'is_active' => 1,
            'is_sod' => 1,
            'sod_price' => 350,
            'sod_currency' => 'KES',
            'serve_on_main_live' => 0,
            'donations_enabled' => 1, // Should be forced false for SOD
        ]);

        $response->assertRedirect(route('admin.streams.index'));

        $sodStream = LiveStream::where('title', 'Gusii Live Music Concert VIP Stream')->firstOrFail();
        $this->assertTrue($sodStream->is_sod);
        $this->assertFalse($sodStream->serve_on_main_live);
        $this->assertFalse($sodStream->donations_enabled);
        $this->assertSame(350.0, (float) $sodStream->sod_price);

        // Main public /live broadcast remains active and untouched
        $this->assertTrue($mainLive->fresh()->is_active);

        $livePage = $this->get(route('live.index'));
        $livePage->assertStatus(200);
        $livePage->assertSee('mainLive123');
        $livePage->assertDontSee('concertVip9');

        // Admin index shows SOD badge and direct shareable link
        $adminIndex = $this->actingAs($this->superAdmin)->get(route('admin.streams.index'));
        $adminIndex->assertStatus(200);
        $adminIndex->assertSee('SOD · KES 350');
        $adminIndex->assertSee('Direct Link Only');
        $adminIndex->assertSee($sodStream->sodUrl());
    }

    public function test_sod_stream_on_main_live_redirects_live_page_to_protected_sod_page(): void
    {
        $sodStream = LiveStream::factory()->create([
            'title' => 'Exclusive Boxing Match PPV',
            'platform' => 'youtube',
            'stream_url' => 'https://www.youtube.com/watch?v=boxingPpv77',
            'embed_url' => 'https://www.youtube.com/embed/boxingPpv77',
            'status' => 'live',
            'is_active' => true,
            'is_sod' => true,
            'sod_price' => 500,
            'sod_currency' => 'KES',
            'serve_on_main_live' => true,
            'donations_enabled' => false,
        ]);

        $this->get(route('live.index'))->assertRedirect(route('sod.show', $sodStream->slug));
        $this->get(route('live.show', $sodStream->slug))->assertRedirect(route('sod.show', $sodStream->slug));
    }

    public function test_unpaid_viewer_sees_play_paywall_no_donate_button_and_no_embed_url(): void
    {
        $sodStream = LiveStream::factory()->create([
            'title' => 'Kisii Gospel Mega Concert',
            'platform' => 'youtube',
            'stream_url' => 'https://www.youtube.com/watch?v=secretConcert1',
            'embed_url' => 'https://www.youtube.com/embed/secretConcert1?autoplay=1',
            'status' => 'live',
            'is_active' => true,
            'is_sod' => true,
            'sod_price' => 300,
            'sod_currency' => 'KES',
            'serve_on_main_live' => false,
            'donations_enabled' => false,
        ]);

        $response = $this->get(route('sod.show', $sodStream->slug));
        $response->assertStatus(200);
        $response->assertSee('Play / Stream Now');
        $response->assertSee('KES 300');
        $response->assertSee('Streaming On Demand Paywall');
        $response->assertDontSee('Donate / Support Stream');
        $response->assertDontSee('secretConcert1');
        $response->assertDontSee('<iframe', false);
    }

    public function test_paid_sod_pass_unlocks_buyer_device_and_blocks_shared_link_on_another_device(): void
    {
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/sod-test-session',
                ],
            ], 200),
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                ],
            ], 200),
        ]);

        $paystack = PaymentGateway::where('code', 'paystack')->firstOrFail();
        $paystack->update(['secret_key' => 'sk_test_sod_secret_123', 'is_active' => true]);

        $sodStream = LiveStream::factory()->create([
            'title' => 'Nyamira Festival Live Concert',
            'platform' => 'youtube',
            'stream_url' => 'https://www.youtube.com/watch?v=nyamiraLiv9',
            'embed_url' => 'https://www.youtube.com/embed/nyamiraLiv9?autoplay=1',
            'status' => 'live',
            'is_active' => true,
            'is_sod' => true,
            'sod_price' => 250,
            'sod_currency' => 'KES',
            'serve_on_main_live' => false,
            'donations_enabled' => false,
        ]);

        // 1. Buyer submits paywall form
        $purchase = $this->post(route('sod.purchase', $sodStream->slug), [
            'buyer_phone' => '0712345678',
            'buyer_email' => 'viewer@example.com',
            'buyer_name' => 'Bosire Viewer',
            'gateway_code' => 'paystack',
        ]);

        $purchase->assertRedirect('https://checkout.paystack.com/sod-test-session');

        $pass = StreamAccessPass::where('live_stream_id', $sodStream->id)->firstOrFail();
        $this->assertSame('pending', $pass->status);
        $this->assertSame(250.0, (float) $pass->amount);

        // 2. Paystack redirects back to callback -> binds pass to buyer's browser via HttpOnly cookie
        $cookieName = StreamAccessPass::deviceCookieName($sodStream->id);
        $callback = $this->get(route('sod.callback', [
            'gateway' => 'paystack',
            'reference' => $pass->order_reference,
        ]));

        $callback->assertRedirect(route('sod.show', $sodStream->slug));
        $callback->assertCookie($cookieName);

        $pass->refresh();
        $this->assertSame('paid', $pass->status);
        $this->assertNotNull($pass->device_fingerprint_hash);

        // Extract the plain device key from the queued response cookie to simulate buyer's browser
        $deviceCookie = collect($callback->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $cookieName);
        $this->assertNotNull($deviceCookie);
        $this->assertTrue($deviceCookie->isHttpOnly());

        // 3. Buyer's browser (sending the exact encrypted cookie returned by callback) can watch the live stream and passes heartbeat
        $buyerView = $this->withUnencryptedCookie($cookieName, $deviceCookie->getValue())
            ->get(route('sod.show', $sodStream->slug));
        $buyerView->assertStatus(200);
        $buyerView->assertSee('nyamiraLiv9');
        $buyerView->assertSee('Paid Access Active · Locked to This Device');

        $buyerHeartbeat = $this->withCredentials()
            ->withUnencryptedCookie($cookieName, $deviceCookie->getValue())
            ->getJson(route('sod.heartbeat', $sodStream->slug));
        $buyerHeartbeat->assertStatus(200)->assertJson(['authorized' => true]);

        // 4. Friend on a second device (no cookies at all) opens the shared SOD URL -> BLOCKED, sees paywall, no embed URL
        $this->defaultCookies = [];
        $this->unencryptedCookies = [];

        $friendView = $this->get(route('sod.show', $sodStream->slug));
        $friendView->assertStatus(200);
        $friendView->assertSee('Play / Stream Now');
        $friendView->assertDontSee('nyamiraLiv9');
        $friendView->assertDontSee('<iframe', false);

        // 5. Friend's heartbeat is rejected with 403
        $friendHeartbeat = $this->getJson(route('sod.heartbeat', $sodStream->slug));
        $friendHeartbeat->assertStatus(403)->assertJson(['authorized' => false]);

        // 6. Friend tries to replay the Paystack callback URL with the same order_reference -> REJECTED
        $replay = $this->get(route('sod.callback', [
            'gateway' => 'paystack',
            'reference' => $pass->order_reference,
        ]));
        $replay->assertRedirect(route('sod.show', $sodStream->slug));
        $replay->assertSessionHas('error');
    }

    public function test_admin_can_view_sod_finance_report_export_csv_and_revoke_or_enable_subscribers(): void
    {
        $sodStream = LiveStream::factory()->create([
            'title' => 'Kisii Mega Rhumba Concert',
            'platform' => 'youtube',
            'stream_url' => 'https://www.youtube.com/watch?v=rhumbaLive1',
            'embed_url' => 'https://www.youtube.com/embed/rhumbaLive1?autoplay=1',
            'status' => 'live',
            'is_active' => true,
            'is_sod' => true,
            'sod_price' => 400,
            'sod_currency' => 'KES',
            'serve_on_main_live' => false,
            'donations_enabled' => false,
        ]);

        $plainDeviceKey = 'test_device_key_for_subscriber_1234567890';
        $paidPass = StreamAccessPass::create([
            'live_stream_id' => $sodStream->id,
            'gateway_code' => 'paystack',
            'order_reference' => 'KTV-SOD-PAID0001',
            'external_reference' => 'PSK-REF-001',
            'buyer_name' => 'Kemunto Subscriber',
            'buyer_phone' => '254712345678',
            'buyer_email' => 'kemunto@example.com',
            'amount' => 400,
            'currency' => 'KES',
            'status' => 'paid',
            'access_token' => StreamAccessPass::generateAccessToken(),
            'device_fingerprint_hash' => hash('sha256', $plainDeviceKey),
            'paid_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        StreamAccessPass::create([
            'live_stream_id' => $sodStream->id,
            'gateway_code' => 'mpesa',
            'order_reference' => 'KTV-SOD-PEND0002',
            'buyer_name' => 'Pending Viewer',
            'buyer_phone' => '254799887766',
            'amount' => 400,
            'currency' => 'KES',
            'status' => 'pending',
            'access_token' => StreamAccessPass::generateAccessToken(),
            'expires_at' => now()->addHours(24),
        ]);

        // 1. Admin views the SOD subscribers & finance report
        $report = $this->actingAs($this->superAdmin)->get(route('admin.streams.sod', $sodStream));
        $report->assertStatus(200);
        $report->assertSee('Kisii Mega Rhumba Concert');
        $report->assertSee('KES 400.00');
        $report->assertSee('KTV-SOD-PAID0001');
        $report->assertSee('Kemunto Subscriber');
        $report->assertSee('PAYSTACK');
        $report->assertSee('Revoke Access');

        // 2. Admin can filter subscribers by status and search
        $filtered = $this->actingAs($this->superAdmin)->get(route('admin.streams.sod', [
            'liveStream' => $sodStream,
            'status' => 'paid',
            'search' => 'Kemunto',
        ]));
        $filtered->assertStatus(200);
        $filtered->assertSee('KTV-SOD-PAID0001');
        $filtered->assertDontSee('KTV-SOD-PEND0002');

        // 3. Admin exports finance CSV report
        $csv = $this->actingAs($this->superAdmin)->get(route('admin.streams.sod.export', $sodStream));
        $csv->assertStatus(200);
        $csv->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('KTV-SOD-PAID0001', $csv->streamedContent());
        $this->assertStringContainsString('Kemunto Subscriber', $csv->streamedContent());

        // 4. Admin revokes the paid subscriber's access
        $revoke = $this->actingAs($this->superAdmin)->patch(
            route('admin.streams.sod.passes.status', [$sodStream, $paidPass]),
            ['status' => 'revoked']
        );
        $revoke->assertRedirect();
        $this->assertSame('revoked', $paidPass->fresh()->status);

        // 5. Revoked subscriber is immediately blocked on /sod/{slug} and heartbeat (even with their valid device cookie)
        auth()->logout();
        $cookieName = StreamAccessPass::deviceCookieName($sodStream->id);

        $blockedView = $this->withCookie($cookieName, $plainDeviceKey)
            ->get(route('sod.show', $sodStream->slug));
        $blockedView->assertStatus(200);
        $blockedView->assertSee('Play / Stream Now');
        $blockedView->assertDontSee('rhumbaLive1');

        $blockedHeartbeat = $this->withCredentials()
            ->withCookie($cookieName, $plainDeviceKey)
            ->getJson(route('sod.heartbeat', $sodStream->slug));
        $blockedHeartbeat->assertStatus(403)->assertJson(['authorized' => false]);

        // 6. Revoked subscriber cannot replay callback to self-restore
        $blockedCallback = $this->get(route('sod.callback', [
            'gateway' => 'paystack',
            'reference' => $paidPass->order_reference,
        ]));
        $blockedCallback->assertRedirect(route('sod.show', $sodStream->slug));
        $blockedCallback->assertSessionHas('error');

        // 7. Admin re-enables the subscriber's access
        $enable = $this->actingAs($this->superAdmin)->patch(
            route('admin.streams.sod.passes.status', [$sodStream, $paidPass]),
            ['status' => 'paid']
        );
        $enable->assertRedirect();
        $this->assertSame('paid', $paidPass->fresh()->status);

        // 8. Re-enabled subscriber immediately regains playback and 200 heartbeat on their bound device
        auth()->logout();
        $restoredView = $this->withCookie($cookieName, $plainDeviceKey)
            ->get(route('sod.show', $sodStream->slug));
        $restoredView->assertStatus(200);
        $restoredView->assertSee('rhumbaLive1');
        $restoredView->assertSee('Paid Access Active · Locked to This Device');

        $restoredHeartbeat = $this->withCredentials()
            ->withCookie($cookieName, $plainDeviceKey)
            ->getJson(route('sod.heartbeat', $sodStream->slug));
        $restoredHeartbeat->assertStatus(200)->assertJson(['authorized' => true]);
    }
}
