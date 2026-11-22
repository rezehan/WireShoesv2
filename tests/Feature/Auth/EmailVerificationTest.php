<?php

namespace Tests\Feature\Auth;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_customer_can_see_home_but_cannot_submit_an_order(): void
    {
        $user = User::factory()->unverified()->create(['firebase_uid' => 'customer-1']);
        $this->withoutVite()->actingAsFirebase($user);

        $this->get('/')->assertOk();
        $this->get('/layanan')->assertRedirect('/verify-email');
        $this->get('/verify-email')->assertOk();
        $this->postJson('/layanan/pesanan', [])->assertForbidden();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_verifying_with_firebase_allows_customer_to_create_order(): void
    {
        $user = User::factory()->unverified()->create(['firebase_uid' => 'customer-1']);
        $service = Service::create(['nama' => 'Cuci Sepatu', 'harga' => '24000', 'aktif' => true]);
        $status = WorkStatus::create(['nama' => 'Dipesan', 'urutan' => 1]);
        $this->fakeFirebaseAdmin()->shouldReceive('getUser')->once()->with('customer-1')
            ->andReturn($this->firebaseRecord('customer-1', $user->email, true));

        $this->actingAsFirebase($user)->post('/email/verification-refresh')->assertRedirect('/');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $this->post('/layanan/pesanan', [
            'nama' => 'Test Customer',
            'alamat' => 'Jalan Test',
            'no_telepon' => '0812345678',
            'service_id' => $service->id,
            'harga' => 1,
        ])->assertRedirect('/layanan');

        $order = Order::sole();
        $this->assertSame($user->id, $order->dibuat_oleh);
        $this->assertSame(Order::STATUS_DIPESAN, $order->status);
        $this->assertSame('24000.00', $order->items()->sole()->harga);
        $this->assertDatabaseHas('order_item_status_logs', ['status_id' => $status->id]);
    }

    public function test_refresh_keeps_account_unverified_until_firebase_confirms_email(): void
    {
        $user = User::factory()->unverified()->create(['firebase_uid' => 'customer-1']);
        $this->fakeFirebaseAdmin()->shouldReceive('getUser')->once()->with('customer-1')
            ->andReturn($this->firebaseRecord('customer-1', $user->email));

        $this->actingAsFirebase($user)->from('/verify-email')->post('/email/verification-refresh')
            ->assertRedirect('/verify-email')->assertSessionHas('status', 'verification-pending');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
