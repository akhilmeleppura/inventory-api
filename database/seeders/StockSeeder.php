<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Stock;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class StockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        $warehouses = Warehouse::all();

        if ($products->count() < 4 || $warehouses->count() < 3) {
            return;
        }

        // Product 0: Low stock (< 10 units) -> +30%
        // Standard price = 199.99 * 1.3 = 259.99
        Stock::create([
            'product_id' => $products[0]->id,
            'warehouse_id' => $warehouses[0]->id,
            'quantity' => 5,
            'expires_at' => null,
        ]);

        // Product 1: Mid stock (10-50 units) -> +10%
        // Standard price = 899.99 * 1.1 = 989.99
        Stock::create([
            'product_id' => $products[1]->id,
            'warehouse_id' => $warehouses[0]->id,
            'quantity' => 15,
            'expires_at' => null,
        ]);
        Stock::create([
            'product_id' => $products[1]->id,
            'warehouse_id' => $warehouses[1]->id,
            'quantity' => 20,
            'expires_at' => null,
        ]);

        // Product 2: High stock (> 100 units) -> -20%
        // Standard price = 349.99 * 0.8 = 279.99
        Stock::create([
            'product_id' => $products[2]->id,
            'warehouse_id' => $warehouses[0]->id,
            'quantity' => 50,
            'expires_at' => null,
        ]);
        Stock::create([
            'product_id' => $products[2]->id,
            'warehouse_id' => $warehouses[1]->id,
            'quantity' => 40,
            'expires_at' => null,
        ]);
        Stock::create([
            'product_id' => $products[2]->id,
            'warehouse_id' => $warehouses[2]->id,
            'quantity' => 30,
            'expires_at' => null,
        ]);

        // Product 3: Blended Price Case (total stock = 30, mid tier +10%, base_price = 129.50)
        // Standard price = 129.50 * 1.1 = 142.45
        // Expiring stock: 10 units expiring in 3 days. Expiring price = 142.45 * 0.75 = 106.84
        // Non-expiring stock: 20 units.
        // Blended price: ((20 * 142.45) + (10 * 106.8375)) / 30 = 130.58
        Stock::create([
            'product_id' => $products[3]->id,
            'warehouse_id' => $warehouses[0]->id,
            'quantity' => 10,
            'expires_at' => Carbon::now()->addDays(3),
        ]);
        Stock::create([
            'product_id' => $products[3]->id,
            'warehouse_id' => $warehouses[1]->id,
            'quantity' => 20,
            'expires_at' => null,
        ]);

        // Product 4: Standard/No adjustment tier (51-100 units) -> 0%
        // Total stock = 70. Base price = 249.00.
        // Also has some stock expiring in 5 days (15 units).
        Stock::create([
            'product_id' => $products[4]->id,
            'warehouse_id' => $warehouses[0]->id,
            'quantity' => 15,
            'expires_at' => Carbon::now()->addDays(5),
        ]);
        Stock::create([
            'product_id' => $products[4]->id,
            'warehouse_id' => $warehouses[2]->id,
            'quantity' => 55,
            'expires_at' => Carbon::now()->addDays(20),
        ]);

        // Product 5: Purely near-expiring stock (all expiring within 7 days)
        // Total stock = 12. Mid tier (+10%), all 12 expiring in 4 days.
        // Expected price: Standard (base * 1.1) * 0.75 = base * 0.825
        Stock::create([
            'product_id' => $products[5]->id,
            'warehouse_id' => $warehouses[1]->id,
            'quantity' => 12,
            'expires_at' => Carbon::now()->addDays(4),
        ]);
    }
}
