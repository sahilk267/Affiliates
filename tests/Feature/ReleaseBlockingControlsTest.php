<?php

namespace Tests\Feature;

use App\Click;
use App\Commission;
use App\Conversion;
use App\Link;
use App\PointsRedemption;
use App\Program;
use App\Services\PayoutService;
use App\Services\PointsService;
use App\User;
use App\UserPoints;
use App\PointsTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReleaseBlockingControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_clean_schema_contains_canonical_financial_columns(): void
    {
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('conversions', 'partner_event_id'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('conversions', 'conversion_value'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('conversions', 'processed_at'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('commissions', 'commission_type'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('commissions', 'payout_method'));
        $this->assertTrue(DB::getSchemaBuilder()->hasColumn('points_transactions', 'idempotency_key'));
    }

    public function test_login_uses_laravel_guard_and_status_reads_the_same_identity(): void
    {
        $user = User::create([
            'name' => 'Affiliate User',
            'email' => 'affiliate@example.test',
            'password' => 'secret-password',
            'role' => User::ROLE_AFFILIATE,
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect('/admin/ui/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->getJson('/auth/status')
            ->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_non_admin_authenticated_user_cannot_access_admin_routes(): void
    {
        $user = User::create([
            'name' => 'Affiliate User',
            'email' => 'affiliate-admin-test@example.test',
            'password' => 'secret-password',
            'role' => User::ROLE_AFFILIATE,
            'is_active' => true,
        ]);

        $this->actingAs($user)->getJson('/admin/dashboard')->assertForbidden();
    }

    public function test_conversion_and_points_mutations_require_partner_signature(): void
    {
        $this->postJson('/api/points/credit', [
            'user_id' => 1,
            'points' => 10,
            'description' => 'test credit',
            'idempotency_key' => 'test-key',
        ])->assertUnauthorized();
    }

    public function test_points_credit_is_idempotent_for_the_same_key(): void
    {
        $user = User::create([
            'name' => 'Points User',
            'email' => 'points@example.test',
            'password' => 'secret-password',
            'role' => User::ROLE_AFFILIATE,
            'is_active' => true,
        ]);

        $service = app(PointsService::class);
        $first = $service->creditPoints($user->id, 10, 'test credit', PointsTransaction::REF_BONUS, null, 'same-key');
        $second = $service->creditPoints($user->id, 10, 'test credit', PointsTransaction::REF_BONUS, null, 'same-key');

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second?->id);
        $this->assertSame(1, PointsTransaction::where('idempotency_key', 'same-key')->count());
        $this->assertSame(10, (int) $user->fresh()->points()->value('balance'));
    }

    public function test_withdrawal_is_atomic_idempotent_and_refunds_on_rejection(): void
    {
        $user = User::create([
            'name' => 'Payout User',
            'email' => 'payout@example.test',
            'password' => 'secret-password',
            'role' => User::ROLE_AFFILIATE,
            'bank_account' => '1234567890',
            'ifsc_code' => 'TEST0001234',
            'is_active' => true,
        ]);
        UserPoints::create([
            'user_id' => $user->id,
            'balance' => 300,
            'pending_balance' => 0,
            'total_earned' => 300,
            'total_redeemed' => 0,
        ]);

        $payouts = app(PayoutService::class);
        $first = $payouts->createWithdrawal($user, 100, 'withdrawal-key');
        $second = $payouts->createWithdrawal($user, 100, 'withdrawal-key');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, PointsRedemption::where('idempotency_key', 'withdrawal-key')->count());
        $this->assertSame(200, (int) $user->fresh()->points()->value('balance'));

        $payouts->rejectRedemption($first, $user->id, 'Rejected for test');
        $this->assertSame(300, (int) $user->fresh()->points()->value('balance'));
        $this->assertSame(PointsRedemption::STATUS_REJECTED, $first->fresh()->status);
        $this->assertNotNull($first->fresh()->refund_transaction_id);
    }

    public function test_commission_cannot_be_paid_before_approval(): void
    {
        $user = User::create([
            'name' => 'Commission User',
            'email' => 'commission@example.test',
            'password' => 'secret-password',
            'role' => User::ROLE_AFFILIATE,
            'is_active' => true,
        ]);
        $program = Program::create([
            'name' => 'Commission Merchant',
            'slug' => 'commission-merchant',
            'type' => 'ecommerce',
            'merchant_name' => 'Commission Merchant',
            'merchant_url' => 'https://merchant.example.test',
            'commission_structure' => ['percentage' => 5],
            'status' => 'active',
        ]);
        $link = Link::create([
            'program_id' => $program->id,
            'user_id' => $user->id,
            'original_url' => 'https://merchant.example.test/item',
            'affiliate_url' => 'https://merchant.example.test/item?ref=test',
            'short_code' => 'commission-test',
            'is_active' => true,
        ]);
        $click = Click::create([
            'link_id' => $link->id,
            'user_id' => $user->id,
            'program_id' => $program->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'device_type' => 'desktop',
            'browser' => 'Unknown',
            'os' => 'Unknown',
            'is_unique' => true,
            'is_converted' => false,
            'clicked_at' => now(),
        ]);
        $conversion = Conversion::create([
            'user_id' => $user->id,
            'program_id' => $program->id,
            'click_id' => $click->id,
            'link_id' => $link->id,
            'event_type' => Conversion::EVENT_PURCHASE,
            'commission_amount' => 10,
            'conversion_id' => 'test-conversion-' . $user->id,
        ]);
        $commission = Commission::create([
            'conversion_id' => $conversion->id,
            'user_id' => $user->id,
            'amount' => 10,
            'status' => Commission::STATUS_PENDING,
            'commission_type' => Commission::TYPE_AFFILIATE,
        ]);

        $this->expectException(\DomainException::class);
        app(PayoutService::class)->payCommission($commission, $user->id, 'bank_transfer', 'tx-before-approval');
    }

    public function test_health_endpoint_checks_the_database_connection(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('database', 'connected');
    }

    public function test_security_headers_are_present_on_http_responses(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()');
    }

    public function test_authenticated_user_cannot_read_another_users_points_balance(): void
    {
        $owner = User::create([
            'name' => 'Balance Owner',
            'email' => 'balance-owner@example.test',
            'password' => 'secret-password',
            'role' => User::ROLE_AFFILIATE,
            'is_active' => true,
        ]);
        $reader = User::create([
            'name' => 'Balance Reader',
            'email' => 'balance-reader@example.test',
            'password' => 'secret-password',
            'role' => User::ROLE_AFFILIATE,
            'is_active' => true,
        ]);

        $this->actingAs($reader)->getJson('/api/points/balance/' . $owner->id)->assertForbidden();
    }

    public function test_signed_conversion_creates_one_attributed_commission_and_is_idempotent(): void
    {
        $user = User::create([
            'name' => 'Conversion User',
            'email' => 'conversion@example.test',
            'password' => 'secret-password',
            'role' => User::ROLE_AFFILIATE,
            'is_active' => true,
        ]);
        $program = Program::create([
            'name' => 'Test Merchant',
            'slug' => 'test-merchant',
            'type' => 'ecommerce',
            'merchant_name' => 'Test Merchant',
            'merchant_url' => 'https://merchant.example.test',
            'commission_structure' => ['percentage' => 5],
            'status' => 'active',
        ]);
        $link = Link::create([
            'program_id' => $program->id,
            'user_id' => $user->id,
            'original_url' => 'https://merchant.example.test/product',
            'affiliate_url' => 'https://merchant.example.test/product?ref=affiliate',
            'short_code' => 'conv-test',
            'is_active' => true,
        ]);
        $click = Click::create([
            'link_id' => $link->id,
            'user_id' => $user->id,
            'program_id' => $program->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'device_type' => 'desktop',
            'browser' => 'Unknown',
            'os' => 'Unknown',
            'is_unique' => true,
            'is_converted' => false,
            'clicked_at' => now(),
        ]);
        $payload = [
            'click_id' => $click->id,
            'partner_event_id' => 'partner-event-1',
            'event_type' => 'purchase',
            'conversion_value' => 100,
            'currency' => 'INR',
            'order_id' => 'order-1',
        ];
        $body = json_encode($payload);
        $timestamp = (string) time();
        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_AFFILIATE_KEY' => 'test-api-key',
            'HTTP_X_AFFILIATE_TIMESTAMP' => $timestamp,
            'HTTP_X_AFFILIATE_SIGNATURE' => hash_hmac('sha256', $timestamp . '.' . $body, 'test-api-secret'),
            'HTTP_IDEMPOTENCY_KEY' => 'partner-event-1',
        ];

        $first = $this->call('POST', '/api/affiliate/conversion', [], [], [], $headers, $body);
        $second = $this->call('POST', '/api/affiliate/conversion', [], [], [], $headers, $body);

        $first->assertOk();
        $second->assertOk();
        $this->assertSame(1, Conversion::where('partner_event_id', 'partner-event-1')->count());
        $this->assertSame(1, $link->fresh()->conversion_count);
        $this->assertSame(1, $link->fresh()->conversions()->count());
        $this->assertTrue($click->fresh()->is_converted);
    }
}
