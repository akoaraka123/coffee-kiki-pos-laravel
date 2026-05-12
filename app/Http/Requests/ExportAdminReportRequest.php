<?php

namespace App\Http\Requests;

use App\Services\AdminReportExportService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ExportAdminReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reportType' => ['required', 'string', 'in:'.AdminReportExportService::TYPE_SALES.','.AdminReportExportService::TYPE_TRANSACTION.','.AdminReportExportService::TYPE_INVENTORY],
            'startDate' => ['required', 'date_format:Y-m-d'],
            'endDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:startDate'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $start = Carbon::parse($this->input('startDate'))->startOfDay();
            $end = Carbon::parse($this->input('endDate'))->endOfDay();
            if ($start->diffInDays($end) > 366) {
                $validator->errors()->add('endDate', 'Date range cannot exceed one year.');
            }
        });
    }
}
