<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Stock;
use App\Services\DynamicPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class DynamicPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private DynamicPricingService $service;
    private Product $product;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DynamicPricingService();
        
        $this->product = Product::create([
            'name' => 'Test Widget',
            'base_price' => 100.00,
            'description' => 'A test product'
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Main Test Warehouse',
            'latitude' => 37.774929,
            'longitude' => -122.419416,
        ]);
    }

    /**
     * Test low stock tier (< 10 units) adds +30%.
     */
    public function test_low_stock_adds_thirty_percent(): void
    {
        Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
            'expires_at' => null,
        ]);

        $price = $this->service->calculate($this->product);

        $this->assertEquals(130.00, $price); // 100 * 1.3
    }

    /**
     * Test mid stock tier (10-50 units) adds +10%.
     */
    public function test_mid_stock_adds_ten_percent(): void
    {
        Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 25,
            'expires_at' => null,
        ]);

        $price = $this->service->calculate($this->product);

        $this->assertEquals(110.00, $price); // 100 * 1.1
    }

    /**
     * Test standard stock tier (51-100 units) has 0% adjustment.
     */
    public function test_standard_stock_has_no_adjustment(): void
    {
        Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 75,
            'expires_at' => null,
        ]);

        $price = $this->service->calculate($this->product);

        $this->assertEquals(100.00, $price); // 100 * 1.0
    }

    /**
     * Test high stock tier (> 100 units) deducts 20%.
     */
    public function test_high_stock_deducts_twenty_percent(): void
    {
        Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 150,
            'expires_at' => null,
        ]);

        $price = $this->service->calculate($this->product);

        $this->assertEquals(80.00, $price); // 100 * 0.8
    }

    /**
     * Test blended expiring discount:
     * - Total stock: 20 (mid tier, standard price = 100 * 1.1 = 110)
     * - Expiring stock: 5 units expiring in 3 days. Expiring price = 110 * 0.75 = 82.50
     * - Non-expiring stock: 15 units.
     * - Weighted Blended Price = ((15 * 110) + (5 * 82.50)) / 20 = (1650 + 412.50) / 20 = 2062.50 / 20 = 103.13
     */
    public function test_blended_expiring_discount(): void
    {
        // Expiring in 3 days (within 7 days)
        Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
            'expires_at' => Carbon::now()->addDays(3),
        ]);

        // Non-expiring
        Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 15,
            'expires_at' => null,
        ]);

        $price = $this->service->calculate($this->product);

        $this->assertEquals(103.13, $price);
    }
}
