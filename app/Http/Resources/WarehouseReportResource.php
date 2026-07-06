<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class WarehouseReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stocks = $this->stocks()->with('product')->get();

        // 1. Group by product and sum quantity
        $inventory = $stocks->groupBy('product_id')->map(function ($productStocks) {
            $firstStock = $productStocks->first();
            return [
                'product_id' => $firstStock->product_id,
                'product_name' => $firstStock->product?->name ?? 'Unknown Product',
                'total_quantity' => $productStocks->sum('quantity'),
            ];
        })->values()->all();

        // 2. Identify near-expiring stock (expires_at within the next 7 days)
        $now = Carbon::now();
        $sevenDaysFromNow = Carbon::now()->addDays(7);

        $nearExpiringStock = $stocks->filter(function ($stock) use ($now, $sevenDaysFromNow) {
            return $stock->expires_at !== null &&
                   $stock->expires_at >= $now &&
                   $stock->expires_at <= $sevenDaysFromNow;
        })->map(function ($stock) {
            return [
                'stock_id' => $stock->id,
                'product_id' => $stock->product_id,
                'product_name' => $stock->product?->name ?? 'Unknown Product',
                'quantity' => $stock->quantity,
                'expires_at' => $stock->expires_at->toIso8601String(),
            ];
        })->values()->all();

        return [
            'warehouse' => [
                'id' => $this->id,
                'name' => $this->name,
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
            ],
            'inventory' => $inventory,
            'near_expiring_stock' => $nearExpiringStock,
        ];
    }
}
