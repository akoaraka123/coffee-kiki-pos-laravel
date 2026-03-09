<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MoneyInventory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class AdminMoneyInventoryController extends Controller
{
    public function index(Request $request): View
    {
        $staffId = $request->string('staff')->toString();
        $staffId = $staffId !== '' ? $staffId : null;

        $date = $request->string('date')->toString();
        $date = $date !== '' ? $date : null;

        $staffUsers = User::query()
            ->where('role', 'staff')
            ->orderBy('name')
            ->get(['id', 'name']);

        $base = MoneyInventory::query()
            ->join('users', 'money_inventories.user_id', '=', 'users.id')
            ->selectRaw('money_inventories.date as inv_date, money_inventories.user_id, users.name as staff_name, SUM(money_inventories.denomination * money_inventories.quantity) as total_cash')
            ->groupBy('inv_date', 'money_inventories.user_id', 'users.name')
            ->orderByDesc('inv_date')
            ->orderBy('users.name');

        if ($staffId) {
            $base->where('money_inventories.user_id', $staffId);
        }

        if ($date) {
            $request->validate([
                'date' => ['date_format:Y-m-d'],
            ]);
            $base->whereDate('money_inventories.date', $date);
        }

        $rows = $base->get();

        $keys = $rows
            ->map(fn ($r) => (string) $r->inv_date . '||' . (string) $r->user_id)
            ->values();

        $breakdownsByKey = collect();
        if ($keys->count() > 0) {
            $breakdowns = MoneyInventory::query()
                ->when($staffId, fn ($q) => $q->where('user_id', $staffId))
                ->when($date, fn ($q) => $q->whereDate('date', $date))
                ->get(['user_id', 'date', 'denomination', 'quantity']);

            $breakdownsByKey = $breakdowns
                ->groupBy(fn (MoneyInventory $r) => (string) $r->date->toDateString() . '||' . (string) $r->user_id)
                ->map(function ($items) {
                    /** @var \Illuminate\Support\Collection<int, MoneyInventory> $items */
                    return $items
                        ->sortByDesc(fn (MoneyInventory $r) => (int) $r->denomination)
                        ->map(fn (MoneyInventory $r) => [
                            'denomination' => (int) $r->denomination,
                            'quantity' => (int) $r->quantity,
                            'subtotal' => (int) $r->denomination * (int) $r->quantity,
                        ])
                        ->values();
                });
        }

        $mapped = $rows->map(function ($r) use ($breakdownsByKey) {
            $dateRaw = (string) $r->inv_date;
            $dateDisplay = $dateRaw;
            try {
                $dateDisplay = Carbon::parse($dateRaw)->format('F j, Y (l)');
            } catch (\Throwable $e) {
                $dateDisplay = $dateRaw;
            }

            $key = $dateRaw . '||' . (string) $r->user_id;

            return [
                'date' => $dateRaw,
                'date_display' => $dateDisplay,
                'staff_id' => (string) $r->user_id,
                'staff_name' => (string) ($r->staff_name ?? '—'),
                'total_cash' => (float) ($r->total_cash ?? 0),
                'breakdown' => $breakdownsByKey->get($key, collect())->all(),
            ];
        });

        $perPage = 10;
        $page = max((int) $request->query('page', 1), 1);
        $paged = $mapped->slice(($page - 1) * $perPage, $perPage)->values();
        $inventories = new LengthAwarePaginator(
            $paged,
            $mapped->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.money-inventory.index', [
            'inventories' => $inventories,
            'staffUsers' => $staffUsers,
            'staffId' => $staffId,
            'date' => $date,
        ]);
    }
}
