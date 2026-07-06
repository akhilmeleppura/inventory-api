<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Http\Resources\WarehouseReportResource;

class WarehouseController extends Controller
{
    /**
     * Generate inventory report for a specific warehouse.
     */
    public function report($id)
    {
        $warehouse = Warehouse::findOrFail($id);

        return new WarehouseReportResource($warehouse);
    }
}
