<?php

namespace App\Services;

use App\Models\Product;
use Carbon\Carbon;

class DynamicPricingService
{
    /**
     * Compute the dynamic price for a product based on its total stock and near-expiring stock.
     *
     * Precedence & Order of Operations:
     * 1. Sum total active stock and the near-expiring stock (expiring within next 7 days).
     * 2. Apply quantity-tier adjustment on the base price to get the "standard adjusted price":
     *    - Total stock < 10: +30%
     *    - Total stock 10-50: +10%
     *    - Total stock > 100: -20%
     *    - Total stock 51-100 (unspecified): 0% (base price remains unchanged)
     * 3. Apply a -25% discount only to the near-expiring stock portion:
     *    - Expiring price = standard adjusted price * 0.75
     * 4. Calculate the weighted blended price:
     *    - Blended Price = ((Normal Stock * Standard Adjusted Price) + (Expiring Stock * Expiring Price)) / Total Stock
     *    - Mathematically equivalent to: Standard Adjusted Price * (1 - (0.25 * Expiring Stock / Total Stock))
     * 5. If Total Stock is 0, return the base price.
     *
     * @param Product $product
     * @return float
     */
    public function calculate(Product $product): float
    {
        $basePrice = (float) $product->base_price;

        // Load stocks relationship if not already loaded
        $stocks = $product->stocks;

        if ($stocks->isEmpty()) {
            return round($basePrice, 2);
        }

        $totalStock = $stocks->sum('quantity');

        if ($totalStock <= 0) {
            return round($basePrice, 2);
        }

        // 1. Determine Standard Adjusted Price based on total stock tiers
        $adjustment = 0.0;
        if ($totalStock < 10) {
            $adjustment = 0.30; // +30%
        } elseif ($totalStock >= 10 && $totalStock <= 50) {
            $adjustment = 0.10; // +10%
        } elseif ($totalStock > 100) {
            $adjustment = -0.20; // -20%
        } // 51 to 100 units has 0% adjustment

        $standardPrice = $basePrice * (1 + $adjustment);

        // 2. Determine near-expiring stock (expires_at is within the next 7 days, from now)
        $now = Carbon::now();
        $sevenDaysFromNow = Carbon::now()->addDays(7);

        $expiringStock = $stocks->filter(function ($stock) use ($now, $sevenDaysFromNow) {
            return $stock->expires_at !== null &&
                   $stock->expires_at >= $now &&
                   $stock->expires_at <= $sevenDaysFromNow;
        })->sum('quantity');

        // Safety check to ensure expiring stock doesn't exceed total stock
        if ($expiringStock > $totalStock) {
            $expiringStock = $totalStock;
        }

        if ($expiringStock <= 0) {
            return round($standardPrice, 2);
        }

        // 3. Calculate weighted blended price
        $nonExpiringStock = $totalStock - $expiringStock;
        $expiringPrice = $standardPrice * 0.75; // -25% discount

        $blendedPrice = (($nonExpiringStock * $standardPrice) + ($expiringStock * $expiringPrice)) / $totalStock;

        return round($blendedPrice, 2);
    }
}
