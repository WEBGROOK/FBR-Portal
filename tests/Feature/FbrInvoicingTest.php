<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FbrInvoicingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('FBR Invoicing Gateway');
    }

    public function test_register_page_is_accessible(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Create Shop Admin Account');
    }

    public function test_user_can_register_new_shop_account(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Electronics Shop',
            'email' => 'newshop@store.pk',
            'seller_ntn' => '7654321-0',
            'pos_id' => 'POS-998877',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', [
            'email' => 'newshop@store.pk',
            'pos_id' => 'POS-998877',
            'seller_ntn' => '7654321-0',
        ]);
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@almadina.pk',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Invoice Control Dashboard');
    }

    public function test_upload_page_is_accessible(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/invoices/upload');
        $response->assertStatus(200);
        $response->assertSee('Upload Daily Invoices');
    }

    public function test_reports_and_csv_export(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/reports');
        $response->assertStatus(200);
        $response->assertSee('FBR Financial Reports');

        $exportResponse = $this->actingAs($user)->get('/reports/export');
        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('Content-type', 'text/csv; charset=utf-8');
    }
}
