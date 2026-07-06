<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\DynamicPricingService;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pricingService = app(DynamicPricingService::class);
        $dynamicPrice = $pricingService->calculate($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'base_price' => (float) $this->base_price,
            'dynamic_price' => $dynamicPrice,
            'description' => $this->description,
            'total_stock' => (int) $this->stocks->sum('quantity'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
