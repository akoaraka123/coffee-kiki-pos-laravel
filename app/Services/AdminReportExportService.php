<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AdminReportExportService
{
    public const TYPE_SALES = 'sales';

    public const TYPE_TRANSACTION = 'transaction';

    public const TYPE_INVENTORY = 'inventory';

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function parseDateRange(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        return [$start, $end];
    }

    /**
     * @return Collection<int, Order>
     */
    public function ordersForRange(Carbon $start, Carbon $end): Collection
    {
        return Order::query()
            ->with([
                'creator:id,name',
                'items' => fn ($q) => $q->whereNull('deleted_at'),
            ])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->get();
    }

    public function orderTotal(Order $order): float
    {
        return (float) ($order->total_amount ?? $order->total ?? 0);
    }

    public function paymentReceivedNumeric(Order $order): float
    {
        $total = $this->orderTotal($order);
        $type = strtolower((string) ($order->payment_type ?? 'cash'));

        if ($type === 'cash') {
            if ($order->cash_received !== null && $order->cash_received !== '') {
                return (float) $order->cash_received;
            }

            return $total;
        }

        return $total;
    }

    public function changeNumeric(Order $order): float
    {
        if (strtolower((string) ($order->payment_type ?? 'cash')) !== 'cash') {
            return 0.0;
        }

        return (float) ($order->change_amount ?? 0);
    }

    public function paymentReceivedDisplay(Order $order): string
    {
        return $this->peso($this->paymentReceivedNumeric($order));
    }

    public function changeDisplay(Order $order): string
    {
        if (strtolower((string) ($order->payment_type ?? 'cash')) !== 'cash') {
            return '—';
        }

        return $this->peso($this->changeNumeric($order));
    }

    public function money(float $value): string
    {
        return number_format($value, 2, '.', ',');
    }

    public function peso(float $value): string
    {
        return '₱'.$this->money($value);
    }

    /**
     * Sales rows matching sample layout (no customer / price / payment method columns).
     *
     * @param  Collection<int, Order>  $orders
     * @return array<int, array<string, string>>
     */
    public function buildSalesRows(Collection $orders): array
    {
        $rows = [];
        foreach ($orders as $order) {
            $items = $order->items;
            $dt = $order->created_at ? Carbon::parse($order->created_at) : null;

            $itemsSummary = $items->map(function ($item) {
                return $item->name.' ('.(int) $item->quantity.')';
            })->implode(', ');

            if (strlen($itemsSummary) > 280) {
                $itemsSummary = substr($itemsSummary, 0, 277).'…';
            }

            $totalQty = (int) $items->sum('quantity');

            $rows[] = [
                'receipt_no' => (string) ($order->order_number ?? $order->id),
                'date' => $dt ? $dt->format('M d, Y') : '',
                'time' => $dt ? $dt->format('g:i A') : '',
                'cashier' => (string) ($order->creator->name ?? '—'),
                'items_ordered' => $itemsSummary !== '' ? $itemsSummary : '—',
                'qty' => (string) $totalQty,
                'total_amount' => $this->peso($this->orderTotal($order)),
                'payment' => $this->paymentReceivedDisplay($order),
                'change' => $this->changeDisplay($order),
            ];
        }

        return $rows;
    }

    /**
     * Same sales data as {@see buildSalesRows()} with numeric values for spreadsheet currency cells.
     *
     * @param  Collection<int, Order>  $orders
     * @return array<int, array<string, mixed>>
     */
    public function buildSalesRowsForSpreadsheet(Collection $orders): array
    {
        $rows = [];
        foreach ($orders as $order) {
            $items = $order->items;
            $dt = $order->created_at ? Carbon::parse($order->created_at) : null;

            $itemsSummary = $items->map(function ($item) {
                return $item->name.' ('.(int) $item->quantity.')';
            })->implode(', ');

            if (strlen($itemsSummary) > 280) {
                $itemsSummary = substr($itemsSummary, 0, 277).'…';
            }

            $totalQty = (int) $items->sum('quantity');

            $rows[] = [
                'receipt_no' => (string) ($order->order_number ?? $order->id),
                'date' => $dt ? $dt->format('M d, Y') : '',
                'time' => $dt ? $dt->format('g:i A') : '',
                'cashier' => (string) ($order->creator->name ?? '—'),
                'items_ordered' => $itemsSummary !== '' ? $itemsSummary : '—',
                'qty' => $totalQty,
                'total_amount' => $this->orderTotal($order),
                'payment' => $this->paymentReceivedNumeric($order),
                'change' => $this->changeNumeric($order),
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array<int, array<string, string>>
     */
    public function buildTransactionRows(Collection $orders): array
    {
        $rows = [];
        foreach ($orders as $order) {
            $dt = $order->created_at ? Carbon::parse($order->created_at) : null;
            $base = [
                'receipt_no' => (string) ($order->order_number ?? $order->id),
                'date' => $dt ? $dt->format('M d, Y') : '',
                'time' => $dt ? $dt->format('g:i A') : '',
                'cashier' => (string) ($order->creator->name ?? '—'),
                'customer_name' => (string) ($order->customer_name ?? '—'),
                'payment_received' => $this->paymentReceivedDisplay($order),
                'change' => $this->changeDisplay($order),
                'payment_method' => $this->paymentMethodLabel($order),
            ];

            foreach ($order->items as $item) {
                $rows[] = array_merge($base, [
                    'items_ordered' => (string) $item->name,
                    'quantity' => (string) (int) $item->quantity,
                    'price' => $this->peso((float) $item->price),
                    'total_amount' => $this->peso((float) $item->line_total),
                ]);
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function buildInventoryRows(): array
    {
        return Inventory::query()
            ->orderBy('product_name')
            ->get()
            ->map(function (Inventory $inv) {
                return [
                    'product_name' => (string) $inv->product_name,
                    'category' => (string) ($inv->category ?? '—'),
                    'size' => (string) ($inv->size ?? '—'),
                    'stock_quantity' => (string) (int) $inv->stock_quantity,
                    'low_stock_threshold' => (string) (int) $inv->low_stock_threshold,
                    'status' => $inv->stock_status_label,
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array{
     *   total_sales: float,
     *   total_orders: int,
     *   total_items: int,
     *   total_payments: float,
     *   total_change: float,
     *   date_range: string,
     *   date_range_line: string
     * }
     */
    public function summarizeOrders(Collection $orders, Carbon $start, Carbon $end): array
    {
        $totalSales = $orders->sum(fn (Order $o) => $this->orderTotal($o));
        $totalItems = (int) $orders->sum(fn (Order $o) => (int) $o->items->sum('quantity'));
        $totalPayments = $orders->sum(fn (Order $o) => $this->paymentReceivedNumeric($o));
        $totalChange = $orders->sum(fn (Order $o) => $this->changeNumeric($o));

        return [
            'total_sales' => (float) $totalSales,
            'total_orders' => $orders->count(),
            'total_items' => $totalItems,
            'total_payments' => (float) $totalPayments,
            'total_change' => (float) $totalChange,
            'date_range' => $start->format('M d, Y').' – '.$end->format('M d, Y'),
            'date_range_line' => $start->format('M d, Y').' - '.$end->format('M d, Y'),
        ];
    }

    /**
     * @return array{total_skus: int, total_units: int, date_range: string, date_range_line: string}
     */
    public function summarizeInventory(Carbon $start, Carbon $end): array
    {
        $rows = Inventory::query()->get();

        return [
            'total_skus' => $rows->count(),
            'total_units' => (int) $rows->sum('stock_quantity'),
            'date_range' => $start->format('M d, Y').' – '.$end->format('M d, Y'),
            'date_range_line' => $start->format('M d, Y').' - '.$end->format('M d, Y'),
        ];
    }

    private function paymentMethodLabel(Order $order): string
    {
        $raw = (string) ($order->payment_type ?? 'cash');
        $label = ucfirst(str_replace('_', ' ', $raw));

        if ($raw !== '' && stripos($raw, 'gcash') !== false && $order->gcash_reference) {
            $label .= ' (Ref: '.$order->gcash_reference.')';
        }

        return $label !== '' ? $label : 'Cash';
    }
}
