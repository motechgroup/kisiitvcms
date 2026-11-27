<?php

namespace Tests\Feature;

use App\Mail\EventTicketMail;
use App\Models\Article;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventTicketOrder;
use App\Models\PaymentGateway;
use App\Models\Role;
use App\Models\User;
use App\Notifications\KisiiTvResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EventTicketingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
        ]);

        $this->superAdmin = User::factory()->create([
            'username' => 'eventadmin',
            'is_active' => true,
        ]);
        $this->superAdmin->roles()->attach($role->id);
    }

    public function test_admin_can_create_event_with_ticket_tiers_and_public_can_view_it(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.events.store'), [
            'title' => 'Kisii Cultural & Music Gala 2026',
            'summary' => 'An unforgettable evening celebrating Gusii music and arts.',
            'description' => 'Live performances, cultural showcases, and dinner.',
            'venue' => 'Gusii Stadium',
            'city' => 'Kisii Town',
            'starts_at' => now()->addDays(10)->format('Y-m-d H:i:s'),
            'currency' => 'KES',
            'is_published' => 1,
            'is_featured' => 1,
            'ticket_types' => [
                ['name' => 'Regular', 'description' => 'General entry', 'price' => 500, 'quantity_available' => 100],
                ['name' => 'VIP', 'description' => 'Front row + lounge', 'price' => 2500, 'quantity_available' => 20],
            ],
        ]);

        $response->assertRedirect(route('admin.events.index'));

        $event = Event::where('title', 'Kisii Cultural & Music Gala 2026')->firstOrFail();
        $this->assertCount(2, $event->ticketTypes);

        $this->get(route('events.index'))
            ->assertStatus(200)
            ->assertSee('Kisii Cultural & Music Gala 2026');

        $this->get(route('events.show', $event->slug))
            ->assertStatus(200)
            ->assertSee('Regular')
            ->assertSee('VIP')
            ->assertSee('Gusii Stadium');
    }

    public function test_free_ticket_purchase_issues_pass_and_dispatches_email_and_sms(): void
    {
        Mail::fake();
        Http::fake([
            'https://api.africastalking.com/*' => Http::response(['SMSMessageData' => ['Message' => 'Sent']], 201),
        ]);

        config([
            'services.sms.driver' => 'africastalking',
            'services.sms.africastalking_username' => 'kisiitv',
            'services.sms.africastalking_api_key' => 'test-at-key',
            'services.sms.sender_id' => 'KISIITV',
        ]);

        $event = Event::create([
            'title' => 'Kisii County Devolution Town Hall',
            'slug' => 'kisii-devolution-town-hall',
            'venue' => 'Kisii Cultural Hall',
            'city' => 'Kisii',
            'starts_at' => now()->addDays(5),
            'currency' => 'KES',
            'is_published' => true,
        ]);

        $tier = $event->ticketTypes()->create([
            'name' => 'Free Community Pass',
            'price' => 0,
            'quantity_available' => 50,
            'is_active' => true,
        ]);

        $response = $this->post(route('events.purchase', $event->slug), [
            'event_ticket_type_id' => $tier->id,
            'quantity' => 2,
            'buyer_name' => 'Kemunto Nyambane',
            'buyer_email' => 'kemunto@example.com',
            'buyer_phone' => '0712345678',
        ]);

        $order = EventTicketOrder::firstOrFail();
        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->email_sent_at);
        $this->assertNotNull($order->sms_sent_at);
        $this->assertCount(2, $order->tickets);
        $this->assertSame(2, $tier->fresh()->quantity_sold);

        $firstTicket = $order->tickets->first();
        $response->assertRedirect(route('tickets.show', $firstTicket->access_token));

        Mail::assertSent(EventTicketMail::class, function (EventTicketMail $mail) {
            return $mail->hasTo('kemunto@example.com');
        });

        Http::assertSent(function ($request) use ($firstTicket) {
            return str_contains($request->url(), 'africastalking.com')
                && $request['to'] === '+254712345678'
                && str_contains($request['message'], $firstTicket->ticket_code)
                && str_contains($request['message'], $firstTicket->passUrl());
        });

        $this->get(route('tickets.show', $firstTicket->access_token))
            ->assertStatus(200)
            ->assertSee($firstTicket->ticket_code)
            ->assertSee('Kemunto Nyambane')
            ->assertSee('Valid Ticket');
    }

    public function test_paid_ticket_via_paystack_verifies_callback_issues_tickets_and_supports_admin_check_in(): void
    {
        Mail::fake();
        Http::fake([
            'https://api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/ticket-session-xyz'],
            ], 200),
            'https://api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success'],
            ], 200),
        ]);

        PaymentGateway::ensureDefaultsExist();
        $paystack = PaymentGateway::where('code', 'paystack')->firstOrFail();
        $paystack->update([
            'is_active' => true,
            'public_key' => 'pk_test_123',
            'secret_key' => 'sk_test_456',
        ]);

        $event = Event::create([
            'title' => 'Kisii TV Awards Night',
            'slug' => 'kisii-tv-awards-night',
            'venue' => 'Ciala Resort',
            'city' => 'Kisumu',
            'starts_at' => now()->addDays(14),
            'currency' => 'KES',
            'is_published' => true,
        ]);

        $vipTier = $event->ticketTypes()->create([
            'name' => 'VIP Dinner Pass',
            'price' => 3000,
            'quantity_available' => 30,
            'is_active' => true,
        ]);

        $purchase = $this->post(route('events.purchase', $event->slug), [
            'event_ticket_type_id' => $vipTier->id,
            'quantity' => 1,
            'buyer_name' => 'Ombati Mokaya',
            'buyer_email' => 'ombati@example.com',
            'buyer_phone' => '0722111222',
            'gateway_code' => 'paystack',
        ]);

        $purchase->assertRedirect('https://checkout.paystack.com/ticket-session-xyz');

        $order = EventTicketOrder::firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame(3000.0, (float) $order->total_amount);

        // Simulate Paystack callback redirect
        $callback = $this->get(route('events.checkout.callback', [
            'gateway' => 'paystack',
            'reference' => $order->order_reference,
        ]));

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertCount(1, $order->tickets);

        $ticket = $order->tickets->first();
        $callback->assertRedirect(route('tickets.show', $ticket->access_token));
        Mail::assertSent(EventTicketMail::class);

        // Admin checks in the ticket at the gate
        $this->actingAs($this->superAdmin)
            ->patch(route('admin.events.tickets.check-in', $ticket))
            ->assertSessionHas('success');

        $this->assertSame('checked_in', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->checked_in_at);

        // Duplicate check-in is prevented
        $this->actingAs($this->superAdmin)
            ->patch(route('admin.events.tickets.check-in', $ticket))
            ->assertSessionHas('error');
    }

    public function test_admin_can_configure_and_test_textsms_kenya_gateway_from_backend(): void
    {
        Http::fake([
            'https://sms.textsms.co.ke/api/services/sendsms/*' => Http::response([
                'responses' => [
                    [
                        'response-code' => 200,
                        'response-description' => 'Success',
                        'mobile' => 254712345678,
                        'messageid' => 987654321,
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.settings.test-sms'), [
            'test_phone' => '0712345678',
            'test_message' => 'Hello from Kisii TV Test!',
        ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'sms']));
        $response->assertSessionHas('success');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://sms.textsms.co.ke/api/services/sendsms/'
                && $request['apikey'] === '3dd298222c1ce227857fda7b42e97377'
                && $request['partnerID'] === '14712'
                && $request['shortcode'] === 'TextSMS'
                && $request['mobile'] === '254712345678'
                && $request['message'] === 'Hello from Kisii TV Test!';
        });
    }

    public function test_header_events_menu_and_generated_banner_appear_only_when_an_active_event_exists(): void
    {
        // Demo event migration seeds an active event by default; verify it renders first
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Upcoming Event')
            ->assertSee('Kisii TV Cultural &amp; Music Festival 2026', false);

        // Once all events are removed, the header banner and Events menu hide automatically
        Event::query()->delete();
        Cache::forget('active_header_event_v1');

        $this->get('/')
            ->assertStatus(200)
            ->assertDontSee('Upcoming Event')
            ->assertDontSee('Get Tickets');

        // Create a new active upcoming event
        $event = Event::create([
            'title' => 'Kisii Live Music Night',
            'slug' => 'kisii-live-music-night',
            'venue' => 'Gusii Stadium',
            'city' => 'Kisii',
            'starts_at' => now()->addDays(4),
            'currency' => 'KES',
            'is_published' => true,
            'is_featured' => true,
        ]);

        $event->ticketTypes()->create([
            'name' => 'Regular',
            'price' => 500,
            'is_active' => true,
        ]);

        // Now the homepage header shows the Events menu AND the auto-generated Event Banner
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Upcoming Event')
            ->assertSee('Kisii Live Music Night')
            ->assertSee('Get Tickets')
            ->assertSee(route('events.index'));
    }

    public function test_admin_can_reorder_and_safely_delete_navigation_categories_with_articles(): void
    {
        $catA = Category::create([
            'name' => 'Politics',
            'slug' => 'politics-test',
            'display_order' => 1,
            'is_active' => true,
            'show_in_menu' => true,
        ]);

        $catB = Category::create([
            'name' => 'Business',
            'slug' => 'business-test',
            'display_order' => 2,
            'is_active' => true,
            'show_in_menu' => true,
        ]);

        $article = Article::create([
            'title' => 'Test Article in Business',
            'slug' => 'test-article-in-business',
            'content' => '<p>Body</p>',
            'user_id' => $this->superAdmin->id,
            'category_id' => $catB->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Move Business up above Politics
        $this->actingAs($this->superAdmin)
            ->patch(route('admin.categories.move', $catB), ['direction' => 'up'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertLessThan($catA->fresh()->display_order, $catB->fresh()->display_order);

        // Delete Business even though it has an article -> article is safely reassigned to Politics
        $this->actingAs($this->superAdmin)
            ->delete(route('admin.categories.destroy', $catB))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $catB->id]);
        $this->assertSame($catA->id, $article->fresh()->category_id);
    }

    public function test_phone_only_purchase_auto_creates_and_remembers_user_and_admin_sees_event_analytics(): void
    {
        Mail::fake();
        config(['services.sms.driver' => 'log']);

        $upcoming = Event::create([
            'title' => 'Kisii Tech Summit 2026',
            'slug' => 'kisii-tech-summit-2026',
            'venue' => 'Kisii University',
            'city' => 'Kisii',
            'starts_at' => now()->addDays(7),
            'currency' => 'KES',
            'is_published' => true,
        ]);

        $freeTier = $upcoming->ticketTypes()->create([
            'name' => 'Student Pass',
            'price' => 0,
            'quantity_available' => 100,
            'is_active' => true,
        ]);

        $past = Event::create([
            'title' => 'Kisii Archive Gala 2025',
            'slug' => 'kisii-archive-gala-2025',
            'venue' => 'Kisii Hotel',
            'city' => 'Kisii',
            'starts_at' => now()->subDays(30),
            'currency' => 'KES',
            'is_published' => true,
        ]);

        // 1. Public Upcoming vs Past tabs
        $this->get(route('events.index', ['tab' => 'upcoming']))
            ->assertStatus(200)
            ->assertSee('Kisii Tech Summit 2026');

        $this->get(route('events.index', ['tab' => 'past']))
            ->assertStatus(200)
            ->assertSee('Kisii Archive Gala 2025')
            ->assertDontSee('Kisii Tech Summit 2026');

        // 2. Phone-only purchase (no email, no signup) auto-creates User account
        $this->post(route('events.purchase', $upcoming->slug), [
            'event_ticket_type_id' => $freeTier->id,
            'quantity' => 1,
            'buyer_name' => 'Moraa Nyangena',
            'buyer_phone' => '0799887766',
        ])->assertRedirect();

        $createdUser = User::where('phone', '+254799887766')->first();
        $this->assertNotNull($createdUser);
        $this->assertSame('Moraa Nyangena', $createdUser->name);

        $order = EventTicketOrder::where('event_id', $upcoming->id)->firstOrFail();
        $this->assertSame($createdUser->id, $order->user_id);

        // 3. Returning buyer lookup by phone remembers name
        $this->getJson(route('events.lookup-attendee', ['phone' => '0799887766']))
            ->assertStatus(200)
            ->assertJson([
                'found' => true,
                'name' => 'Moraa Nyangena',
            ]);

        // 4. Email-only purchase (no phone) also works
        $this->post(route('events.purchase', $upcoming->slug), [
            'event_ticket_type_id' => $freeTier->id,
            'quantity' => 1,
            'buyer_email' => 'emailonly@example.com',
        ])->assertRedirect();

        Mail::assertSent(EventTicketMail::class, fn (EventTicketMail $m) => $m->hasTo('emailonly@example.com'));

        // 5. Admin single-event analytics page & dashboard filters & duplicate
        $this->actingAs($this->superAdmin)
            ->get(route('admin.events.show', $upcoming))
            ->assertStatus(200)
            ->assertSee('Kisii Tech Summit 2026')
            ->assertSee('Moraa Nyangena')
            ->assertSee('Student Pass');

        $this->actingAs($this->superAdmin)
            ->get(route('admin.events.index', ['timeline' => 'past']))
            ->assertStatus(200)
            ->assertSee('Kisii Archive Gala 2025')
            ->assertDontSee('Kisii Tech Summit 2026');

        $this->actingAs($this->superAdmin)
            ->post(route('admin.events.duplicate', $upcoming))
            ->assertRedirect();

        $this->assertTrue(Event::where('title', 'Kisii Tech Summit 2026 (Copy)')->exists());
    }

    public function test_admin_can_send_branded_test_ticket_email_from_settings(): void
    {
        Mail::fake();

        $this->actingAs($this->superAdmin)
            ->post(route('admin.settings.test-email'), [
                'test_email' => 'admin-check@kisiitv.co.ke',
            ])
            ->assertRedirect(route('admin.settings.index', ['tab' => 'sms']))
            ->assertSessionHas('success');

        Mail::assertSent(EventTicketMail::class, function (EventTicketMail $mail) {
            return $mail->hasTo('admin-check@kisiitv.co.ke') && $mail->isTestPreview === true;
        });
    }

    public function test_admin_and_users_can_trigger_password_reset_via_hello_kisiitv_mailer(): void
    {
        Notification::fake();

        $staff = User::factory()->create([
            'email' => 'reporter.reset@kisiitv.co.ke',
            'is_active' => true,
        ]);

        // 1. Admin triggers password reset for another user
        $this->actingAs($this->superAdmin)
            ->post(route('admin.users.send-reset-link', $staff))
            ->assertSessionHas('success');

        Notification::assertSentTo(
            $staff,
            KisiiTvResetPasswordNotification::class,
            function (KisiiTvResetPasswordNotification $notification) use ($staff) {
                $mail = $notification->toMail($staff);

                return $mail->from[0] === 'hello@kisiitv.co.ke'
                    && $mail->view === 'emails.password-reset';
            }
        );

        // 2. Admin / user self-service forgot password
        auth()->logout();
        $this->post(route('password.email'), ['email' => $this->superAdmin->email])
            ->assertSessionHas('status');
    }
}
