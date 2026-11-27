<?php

namespace Tests\Feature;

use App\Mail\EventTicketMail;
use App\Models\Event;
use App\Models\EventTicketOrder;
use App\Models\PaymentGateway;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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
}
