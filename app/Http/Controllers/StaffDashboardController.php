<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\View\View;

class StaffDashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalStock = Inventory::sum('stock_quantity');

        return view('staff.dashboard', [
            'totalStock' => $totalStock,
        ]);
    }
}
