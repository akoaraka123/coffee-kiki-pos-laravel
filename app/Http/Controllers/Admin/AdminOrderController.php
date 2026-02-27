<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminOrderController extends Controller
{
    public function index(Request $request): View
    {
        $staffId = $request->string('staff')->toString();
        $staffId = $staffId !== '' ? $staffId : null;

        $summary = $request->string('summary')->toString();
        $summary = $summary !== '' ? $summary : null;

        $staffUsers = User::query()
            ->where('role', 'staff')
            ->orderBy('name')
            ->get(['id', 'name']);

        $ordersQuery = Order::query()
            ->with([
                'creator:id,name,role',
                'items:id,order_id,name,price,quantity,line_total',
            ])
            ->latest();

        if ($staffId) {
            $ordersQuery->where('created_by', $staffId);
        }

        $todaySales = null;
        if ($summary === 'today') {
            $start = Carbon::today();
            $end = Carbon::tomorrow();

            $salesQuery = Order::query()
                ->where('status', 'paid')
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end);

            if ($staffId) {
                $salesQuery->where('created_by', $staffId);
            }

            $todaySales = (float) $salesQuery->sum('total');
        }

        return view('admin.orders.index', [
            'orders' => $ordersQuery->paginate(10)->withQueryString(),
            'staffUsers' => $staffUsers,
            'staffId' => $staffId,
            'summary' => $summary,
            'todaySales' => $todaySales,
        ]);
    }
}
