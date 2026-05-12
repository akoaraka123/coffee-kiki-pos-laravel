<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_reports_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk();
    }

    public function test_staff_cannot_access_reports(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }

    public function test_admin_can_download_excel_sales_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cashier = User::factory()->create(['role' => 'staff']);

        $order = Order::query()->create([
            'order_number' => 'TEST-001',
            'customer_name' => 'Juan',
            'total' => 150.00,
            'total_amount' => 150.00,
            'status' => 'completed',
            'payment_type' => 'cash',
            'cash_received' => 200.00,
            'change_amount' => 50.00,
            'created_by' => $cashier->id,
            'created_at' => Carbon::parse('2026-05-10 14:00:00'),
            'updated_at' => Carbon::parse('2026-05-10 14:00:00'),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => null,
            'name' => 'Latte',
            'price' => 75.00,
            'quantity' => 2,
            'line_total' => 150.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.export.excel', [
            'reportType' => 'sales',
            'startDate' => '2026-05-01',
            'endDate' => '2026-05-31',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
    }

    public function test_export_validates_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->getJson(route('admin.reports.export.pdf', [
            'reportType' => 'sales',
            'startDate' => '2026-05-10',
            'endDate' => '2026-05-01',
        ]))->assertStatus(422);
    }
}
