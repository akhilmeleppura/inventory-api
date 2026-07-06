<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockRequest;
use App\Models\Stock;

class StockController extends Controller
{
    /**
     * Create or update a stock record (upsert).
     */
    public function store(StoreStockRequest $request)
    {
        $validated = $request->validated();

        // Handle upsert including soft-deleted records
        $stock = Stock::withTrashed()
            ->where('product_id', $validated['product_id'])
            ->where('warehouse_id', $validated['warehouse_id'])
            ->first();

        if ($stock) {
            if ($stock->trashed()) {
                $stock->restore();
            }
            $stock->update([
                'quantity' => $validated['quantity'],
                'expires_at' => $validated['expires_at'] ?? null,
            ]);
            $statusCode = 200; // Updated
        } else {
            $stock = Stock::create($validated);
            $statusCode = 201; // Created
        }

        // Return the fresh stock record with relationships loaded
        return response()->json([
            'message' => $statusCode === 201 ? 'Stock record created successfully.' : 'Stock record updated successfully.',
            'data' => [
                'id' => $stock->id,
                'product_id' => $stock->product_id,
                'warehouse_id' => $stock->warehouse_id,
                'quantity' => $stock->quantity,
                'expires_at' => $stock->expires_at?->toIso8601String(),
                'created_at' => $stock->created_at?->toIso8601String(),
                'updated_at' => $stock->updated_at?->toIso8601String(),
            ]
        ], $statusCode);
    }
}
