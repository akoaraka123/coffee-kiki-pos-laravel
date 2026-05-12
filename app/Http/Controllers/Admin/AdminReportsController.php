<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportAdminReportRequest;
use App\Models\Order;
use App\Services\AdminReportExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportsController extends Controller
{
    private const EXCEL_HEADER_FILL = 'C6EFCE';

    private const PESO_FORMAT = '"₱"#,##0.00';

    public function __construct(
        private readonly AdminReportExportService $reports
    ) {}

    public function index(): View
    {
        $end = Carbon::today()->format('Y-m-d');
        $start = Carbon::today()->startOfMonth()->format('Y-m-d');

        return view('admin.reports.index', [
            'defaultStart' => $start,
            'defaultEnd' => $end,
        ]);
    }

    public function exportPdf(ExportAdminReportRequest $request): Response
    {
        $payload = $this->buildPayload($request);

        $logoPath = public_path('images/khopi-kiki-logo.png');
        $logoSrc = '';
        if (is_string($logoPath) && is_readable($logoPath)) {
            $data = @file_get_contents($logoPath);
            if ($data !== false) {
                $mime = str_ends_with(strtolower($logoPath), '.png') ? 'image/png' : 'image/jpeg';
                $logoSrc = 'data:'.$mime.';base64,'.base64_encode($data);
            }
        }

        $view = $payload['reportType'] === AdminReportExportService::TYPE_SALES
            ? 'admin.reports.pdf-sales'
            : 'admin.reports.pdf';

        $pdf = Pdf::loadView($view, array_merge($payload, [
            'logoSrc' => $logoSrc,
            'generatedAt' => Carbon::now()->format('F j, Y g:i A'),
            'generatedBy' => $payload['generatedBy'],
        ]))
            ->setPaper('a4', 'landscape');

        $filename = $this->filename($payload['reportType'], $payload['startDate'], $payload['endDate'], 'pdf');

        return $pdf->download($filename);
    }

    public function exportExcel(ExportAdminReportRequest $request): StreamedResponse
    {
        $payload = $this->buildPayload($request);
        $filename = $this->filename($payload['reportType'], $payload['startDate'], $payload['endDate'], 'xlsx');
        $generatedBy = $payload['generatedBy'];

        return response()->streamDownload(function () use ($payload, $generatedBy): void {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();

            if ($payload['reportType'] === AdminReportExportService::TYPE_SALES) {
                $this->writeSalesSampleExcel($sheet, $payload, $generatedBy);
            } else {
                $this->writeGenericExcel($sheet, $payload, $generatedBy);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeSalesSampleExcel(Worksheet $sheet, array $payload, string $generatedBy): void
    {
        $lastCol = 'I';
        $sheet->setTitle('Sales Report');

        $logoPath = public_path('images/khopi-kiki-logo.png');
        if (is_readable($logoPath)) {
            $drawing = new Drawing;
            $drawing->setName('Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(68);
            $drawing->setCoordinates('D1');
            $drawing->setOffsetX(10);
            $drawing->setWorksheet($sheet);
        }
        $sheet->getRowDimension(1)->setRowHeight(52);

        $r = 2;
        $this->excelMergeCenter($sheet, 'A'.$r.':'.$lastCol.$r, 'KOPHI KIKI', true, 14);
        $r++;
        $this->excelMergeCenter($sheet, 'A'.$r.':'.$lastCol.$r, 'KIK-LIGIN KA SA SARAP', true, 11);
        $r++;
        $this->excelMergeCenter($sheet, 'A'.$r.':'.$lastCol.$r, 'Tatsulok Night Market, Fil-Am Avenue in Barangay Fatima, General Santos City', false, 10);
        $r++;
        $this->excelMergeCenter($sheet, 'A'.$r.':'.$lastCol.$r, '09920307525', false, 10);
        $r += 2;

        $this->excelMergeCenter($sheet, 'A'.$r.':'.$lastCol.$r, 'SALES REPORT', true, 14);
        $r++;
        $this->excelMergeCenter($sheet, 'A'.$r.':'.$lastCol.$r, 'Date Range: '.($payload['summary']['date_range_line'] ?? ''), false, 10);
        $r += 2;

        $headerRow = $r;
        $headers = [
            'Receipt No.',
            'Date',
            'Time',
            'Cashier',
            'Items Ordered',
            'Qty',
            'Total Amount',
            'Payment',
            'Change',
        ];
        $col = 1;
        foreach ($headers as $label) {
            $this->setCellCr($sheet, $col, $r, $label);
            $col++;
        }
        $sheet->getStyle('A'.$r.':'.$lastCol.$r)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::EXCEL_HEADER_FILL]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);
        $r++;

        /** @var Collection<int, Order> $orders */
        $orders = $payload['orders'];
        $rows = $this->reports->buildSalesRowsForSpreadsheet($orders);
        $dataStart = $r;

        foreach ($rows as $row) {
            $this->setCellCr($sheet, 1, $r, $row['receipt_no']);
            $this->setCellCr($sheet, 2, $r, $row['date']);
            $this->setCellCr($sheet, 3, $r, $row['time']);
            $this->setCellCr($sheet, 4, $r, $row['cashier']);
            $this->setCellCr($sheet, 5, $r, $row['items_ordered']);
            $this->setCellCr($sheet, 6, $r, (int) $row['qty']);
            $this->setCellCr($sheet, 7, $r, (float) $row['total_amount']);
            $this->setCellCr($sheet, 8, $r, (float) $row['payment']);
            $this->setCellCr($sheet, 9, $r, (float) $row['change']);
            $r++;
        }
        $dataEnd = max($dataStart, $r - 1);

        if ($dataEnd >= $dataStart) {
            $sheet->getStyle('A'.$dataStart.':'.$lastCol.$dataEnd)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);
            $sheet->getStyle('F'.$dataStart.':F'.$dataEnd)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            foreach (['G', 'H', 'I'] as $colLetter) {
                $sheet->getStyle($colLetter.$dataStart.':'.$colLetter.$dataEnd)->getNumberFormat()->setFormatCode(self::PESO_FORMAT);
                $sheet->getStyle($colLetter.$dataStart.':'.$colLetter.$dataEnd)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        }

        $r += 2;
        $sheet->setCellValue('A'.$r, 'SUMMARY');
        $sheet->mergeCells('A'.$r.':B'.$r);
        $sheet->getStyle('A'.$r)->getFont()->setBold(true);
        $sheet->getStyle('A'.$r.':B'.$r)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::EXCEL_HEADER_FILL]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ]);
        $r++;

        $summaryRows = [
            ['Total Sales', (float) ($payload['summary']['total_sales'] ?? 0), true],
            ['Total Orders', (int) ($payload['summary']['total_orders'] ?? 0), false],
            ['Total Items Sold', (int) ($payload['summary']['total_items'] ?? 0), false],
            ['Total Payments', (float) ($payload['summary']['total_payments'] ?? 0), true],
            ['Total Change', (float) ($payload['summary']['total_change'] ?? 0), true],
            ['Report Generated By', $generatedBy, false],
            ['Generated Date & Time', Carbon::now()->format('F j, Y g:i A'), false],
        ];

        foreach ($summaryRows as [$label, $value, $isMoney]) {
            $sheet->setCellValue('A'.$r, $label);
            $sheet->setCellValue('B'.$r, $value);
            $sheet->getStyle('A'.$r)->getFont()->setBold(true);
            $sheet->getStyle('A'.$r.':B'.$r)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);
            if ($isMoney) {
                $sheet->getStyle('B'.$r)->getNumberFormat()->setFormatCode(self::PESO_FORMAT);
                $sheet->getStyle('B'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            } elseif (is_numeric($value) && $label !== 'Total Orders' && $label !== 'Total Items Sold') {
                // skip
            } elseif ($label === 'Total Orders' || $label === 'Total Items Sold') {
                $sheet->getStyle('B'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            } else {
                $sheet->getStyle('B'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
            $r++;
        }

        foreach (range(1, 9) as $i) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $freezeRow = $headerRow + 1;
        $sheet->freezePane('A'.$freezeRow);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeGenericExcel(Worksheet $sheet, array $payload, string $generatedBy): void
    {
        $title = match ($payload['reportType']) {
            AdminReportExportService::TYPE_TRANSACTION => 'Transaction Report',
            default => 'Inventory Report',
        };
        $sheet->setTitle(substr($title, 0, 31));

        $lastCol = Coordinate::stringFromColumnIndex(max(count($payload['headers']), 1));
        $r = 1;

        $logoPath = public_path('images/khopi-kiki-logo.png');
        if (is_readable($logoPath)) {
            $drawing = new Drawing;
            $drawing->setName('Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(60);
            $drawing->setCoordinates('B1');
            $drawing->setWorksheet($sheet);
            $sheet->getRowDimension(1)->setRowHeight(48);
        }
        $r = 2;

        $this->excelMergeCenter($sheet, 'A'.$r.':'.$lastCol.$r, 'KOPHI KIKI', true, 13);
        $r++;
        $this->excelMergeCenter($sheet, 'A'.$r.':'.$lastCol.$r, 'KIK-LIGIN KA SA SARAP', true, 10);
        $r++;
        $this->excelMergeCenter($sheet, 'A'.$r.':'.$lastCol.$r, 'Tatsulok Night Market, Fil-Am Avenue in Barangay Fatima, General Santos City', false, 9);
        $r++;
        $this->excelMergeCenter($sheet, 'A'.$r.':'.$lastCol.$r, '09920307525', false, 9);
        $r += 2;

        $this->excelMergeCenter($sheet, 'A'.$r.':'.$lastCol.$r, strtoupper($title), true, 13);
        $r++;
        $this->excelMergeCenter($sheet, 'A'.$r.':'.$lastCol.$r, 'Date Range: '.($payload['summary']['date_range_line'] ?? ''), false, 10);
        $r += 2;

        $colKeys = array_keys($payload['headers']);
        $headerRow = $r;
        $c = 1;
        foreach ($payload['headers'] as $label) {
            $this->setCellCr($sheet, $c, $r, $label);
            $c++;
        }
        $sheet->getStyle('A'.$r.':'.$lastCol.$r)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::EXCEL_HEADER_FILL]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $r++;

        $dataStart = $r;
        foreach ($payload['rows'] as $dataRow) {
            $c = 1;
            foreach ($colKeys as $key) {
                $this->setCellCr($sheet, $c, $r, $dataRow[$key] ?? '');
                $c++;
            }
            $r++;
        }
        $dataEnd = max($dataStart, $r - 1);
        if ($dataEnd >= $dataStart) {
            $sheet->getStyle('A'.$dataStart.':'.$lastCol.$dataEnd)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            ]);
        }

        $r += 2;
        $sheet->setCellValue('A'.$r, 'SUMMARY');
        $sheet->mergeCells('A'.$r.':B'.$r);
        $sheet->getStyle('A'.$r.':B'.$r)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::EXCEL_HEADER_FILL]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $r++;

        if ($payload['reportType'] === AdminReportExportService::TYPE_INVENTORY) {
            $sheet->setCellValue('A'.$r, 'Total SKUs');
            $sheet->setCellValue('B'.$r, (int) ($payload['summary']['total_skus'] ?? 0));
            $r++;
            $sheet->setCellValue('A'.$r, 'Total units in stock');
            $sheet->setCellValue('B'.$r, (int) ($payload['summary']['total_units'] ?? 0));
        } else {
            $sheet->setCellValue('A'.$r, 'Total Sales');
            $sheet->setCellValue('B'.$r, (float) ($payload['summary']['total_sales'] ?? 0));
            $sheet->getStyle('B'.$r)->getNumberFormat()->setFormatCode(self::PESO_FORMAT);
            $r++;
            $sheet->setCellValue('A'.$r, 'Total Orders');
            $sheet->setCellValue('B'.$r, (int) ($payload['summary']['total_orders'] ?? 0));
            $r++;
            $sheet->setCellValue('A'.$r, 'Total Items Sold');
            $sheet->setCellValue('B'.$r, (int) ($payload['summary']['total_items'] ?? 0));
            $r++;
            $sheet->setCellValue('A'.$r, 'Total Payments');
            $sheet->setCellValue('B'.$r, (float) ($payload['summary']['total_payments'] ?? 0));
            $sheet->getStyle('B'.$r)->getNumberFormat()->setFormatCode(self::PESO_FORMAT);
            $r++;
            $sheet->setCellValue('A'.$r, 'Total Change');
            $sheet->setCellValue('B'.$r, (float) ($payload['summary']['total_change'] ?? 0));
            $sheet->getStyle('B'.$r)->getNumberFormat()->setFormatCode(self::PESO_FORMAT);
        }
        $r++;
        $sheet->setCellValue('A'.$r, 'Report Generated By');
        $sheet->setCellValue('B'.$r, $generatedBy);
        $r++;
        $sheet->setCellValue('A'.$r, 'Generated Date & Time');
        $sheet->setCellValue('B'.$r, Carbon::now()->format('F j, Y g:i A'));

        foreach (range(1, count($colKeys)) as $i) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $sheet->freezePane('A'.($headerRow + 1));
    }

    private function excelMergeCenter(Worksheet $sheet, string $range, string $text, bool $bold, int $fontSize): void
    {
        $sheet->mergeCells($range);
        $sheet->setCellValue(explode(':', $range)[0], $text);
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getStyle($range)->getFont()->setBold($bold)->setSize($fontSize);
    }

    /**
     * PhpSpreadsheet 5 removed setCellValueByColumnAndRow(); use explicit A1-style coordinates.
     */
    private function setCellCr(Worksheet $sheet, int $columnIndex1Based, int $row, mixed $value): void
    {
        $coordinate = Coordinate::stringFromColumnIndex($columnIndex1Based).$row;
        $sheet->setCellValue($coordinate, $value);
    }

    /**
     * @return array{
     *   reportType: string,
     *   reportTitle: string,
     *   startDate: string,
     *   endDate: string,
     *   headers: array<string, string>,
     *   rows: array<int, array<string, string>>,
     *   summary: array<string, mixed>,
     *   generatedBy: string,
     *   orders?: Collection<int, Order>
     * }
     */
    private function buildPayload(ExportAdminReportRequest $request): array
    {
        $type = $request->validated('reportType');
        $startDate = $request->validated('startDate');
        $endDate = $request->validated('endDate');
        [$start, $end] = $this->reports->parseDateRange($startDate, $endDate);
        $generatedBy = (string) ($request->user()?->name ?? 'Admin');

        if ($type === AdminReportExportService::TYPE_INVENTORY) {
            $rows = $this->reports->buildInventoryRows();
            $summary = $this->reports->summarizeInventory($start, $end);
            $headers = [
                'product_name' => 'Product',
                'category' => 'Category',
                'size' => 'Size',
                'stock_quantity' => 'Stock quantity',
                'low_stock_threshold' => 'Low stock threshold',
                'status' => 'Status',
            ];
            $reportTitle = 'Inventory Report';

            return [
                'reportType' => $type,
                'reportTitle' => $reportTitle,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'headers' => $headers,
                'rows' => $rows,
                'summary' => array_merge($summary, [
                    'total_sales' => null,
                    'total_orders' => null,
                    'total_items' => null,
                    'total_payments' => null,
                    'total_change' => null,
                ]),
                'generatedBy' => $generatedBy,
            ];
        }

        $orders = $this->reports->ordersForRange($start, $end);
        $summary = $this->reports->summarizeOrders($orders, $start, $end);

        if ($type === AdminReportExportService::TYPE_SALES) {
            return [
                'reportType' => $type,
                'reportTitle' => 'Sales Report',
                'startDate' => $startDate,
                'endDate' => $endDate,
                'headers' => [],
                'rows' => $this->reports->buildSalesRows($orders),
                'summary' => $summary,
                'generatedBy' => $generatedBy,
                'orders' => $orders,
            ];
        }

        $headers = [
            'receipt_no' => 'Receipt No.',
            'date' => 'Date',
            'time' => 'Time',
            'cashier' => 'Cashier',
            'customer_name' => 'Customer name',
            'items_ordered' => 'Items ordered',
            'quantity' => 'Qty',
            'price' => 'Price',
            'total_amount' => 'Total amount',
            'payment_received' => 'Payment',
            'change' => 'Change',
            'payment_method' => 'Payment method',
        ];

        return [
            'reportType' => $type,
            'reportTitle' => 'Transaction Report',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'headers' => $headers,
            'rows' => $this->reports->buildTransactionRows($orders),
            'summary' => $summary,
            'generatedBy' => $generatedBy,
            'orders' => $orders,
        ];
    }

    private function filename(string $type, string $start, string $end, string $ext): string
    {
        $slug = match ($type) {
            AdminReportExportService::TYPE_SALES => 'sales',
            AdminReportExportService::TYPE_TRANSACTION => 'transaction',
            default => 'inventory',
        };

        return 'khopi-kiki-'.$slug.'-report-'.$start.'-to-'.$end.'.'.$ext;
    }
}
