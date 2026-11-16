<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CheckoutAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_submission_requires_authentication(): void
    {
        $this->post('/cart/order', [])
            ->assertRedirect(route('login'));
    }

    public function test_paypal_is_rejected_until_a_real_payment_controller_is_implemented(): void
    {
        $user = User::create(['name' => 'Regular User', 'email' => uniqid('user_', true) . '@example.test', 'password' => Hash::make('password'), 'role' => 'user', 'status' => 'active']);

        $this->actingAs($user)
            ->post('/cart/order', [
                'payment_method' => 'paypal',
            ])
            ->assertSessionHasErrors('payment_method');
    }

    public function test_cart_deletion_requires_authentication(): void
    {
        $this->get('/cart-delete/1')
            ->assertRedirect(route('login'));
    }

    public function test_wishlist_deletion_requires_authentication(): void
    {
        $this->get('/wishlist-delete/1')
            ->assertRedirect(route('login'));
    }

    public function test_income_report_is_not_available_to_a_normal_user(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get('/income')
            ->assertRedirect(route('user'));
    }
}
