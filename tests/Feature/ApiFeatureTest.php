<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class ApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->product = Product::create([
            'name' => 'Premium Wireless Headphones',
            'base_price' => 100.00,
            'description' => 'Noise-cancelling headphones.'
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Silicon Valley Hub',
            'latitude' => 37.403819,
            'longitude' => -122.031267,
        ]);
    }

    /**
     * Test /api/login endpoint.
     */
    public function test_login_authenticates_user_and_issues_token(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'name', 'email']
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized',
                'message' => 'Invalid email or password.'
            ]);
    }

    /**
     * Test /api/products endpoint (Auth required).
     */
    public function test_get_products_requires_auth(): void
    {
        $response = $this->getJson('/api/products');
        $response->assertStatus(401);
    }

    public function test_get_products_returns_list_with_dynamic_prices_when_authenticated(): void
    {
        Sanctum::actingAs($this->user);

        // Low stock: quantity = 5 (+30% price)
        Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
            'expires_at' => null,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $this->product->id,
                'name' => 'Premium Wireless Headphones',
                'base_price' => 100.00,
                'dynamic_price' => 130.00, // 100 * 1.3
                'total_stock' => 5,
            ]);
    }

    /**
     * Test /api/stock endpoint (Auth required & Upsert behavior).
     */
    public function test_post_stock_requires_auth(): void
    {
        $response = $this->postJson('/api/stock', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
        ]);

        $response->assertStatus(401);
    }

    public function test_post_stock_validates_required_fields(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/stock', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id', 'warehouse_id', 'quantity']);
    }

    public function test_post_stock_performs_upsert(): void
    {
        Sanctum::actingAs($this->user);

        // 1. Create Stock record (Insert)
        $response = $this->postJson('/api/stock', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
            'expires_at' => Carbon::now()->addDays(5)->toIso8601String(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.quantity', 10);

        $this->assertDatabaseHas('stock', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
        ]);

        // 2. Update Stock record (Upsert Update)
        $response = $this->postJson('/api/stock', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 25,
            'expires_at' => null,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.quantity', 25);

        $this->assertDatabaseHas('stock', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 25,
            'expires_at' => null,
        ]);
    }

    /**
     * Test /api/warehouses/{id}/report endpoint.
     */
    public function test_get_warehouse_report_returns_detailed_inventory(): void
    {
        Sanctum::actingAs($this->user);

        // Stock 1: Near expiring (3 days)
        Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 8,
            'expires_at' => Carbon::now()->addDays(3),
        ]);

        // Stock 2: Not expiring (20 days)
        $anotherProduct = Product::create([
            'name' => 'Ultra Slim Smartphone',
            'base_price' => 500.00,
        ]);

        Stock::create([
            'product_id' => $anotherProduct->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 15,
            'expires_at' => Carbon::now()->addDays(20),
        ]);

        $response = $this->getJson("/api/warehouses/{$this->warehouse->id}/report");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'warehouse' => ['id', 'name', 'latitude', 'longitude'],
                    'inventory' => [
                        '*' => ['product_id', 'product_name', 'total_quantity']
                    ],
                    'near_expiring_stock' => [
                        '*' => ['stock_id', 'product_id', 'product_name', 'quantity', 'expires_at']
                    ]
                ]
            ])
            ->assertJsonCount(2, 'data.inventory')
            ->assertJsonCount(1, 'data.near_expiring_stock')
            ->assertJsonPath('data.near_expiring_stock.0.product_name', 'Premium Wireless Headphones');
    }
}
