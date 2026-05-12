@extends('layouts.dashboard')

@section('title', 'Reports')

@section('content')
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold">Reports</h2>
            <p class="mt-1 text-sm text-white/50">Export sales and transaction records.</p>
        </div>

        <div class="rounded-xl border border-white/10 bg-white/5 p-6 shadow-sm sm:p-8">
            <form id="reports-export-form" class="space-y-5">
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="startDate" class="text-xs text-white/60">Start date</label>
                        <input
                            type="date"
                            id="startDate"
                            name="startDate"
                            value="{{ $defaultStart }}"
                            required
                            class="mt-2 w-full rounded-xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20"
                        />
                    </div>
                    <div>
                        <label for="endDate" class="text-xs text-white/60">End date</label>
                        <input
                            type="date"
                            id="endDate"
                            name="endDate"
                            value="{{ $defaultEnd }}"
                            required
                            class="mt-2 w-full rounded-xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20"
                        />
                    </div>
                </div>

                <div>
                    <label for="reportType" class="text-xs text-white/60">Report type</label>
                    <select
                        id="reportType"
                        name="reportType"
                        required
                        class="mt-2 w-full rounded-xl border border-white/10 bg-[#1b1b1b] px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-white/20"
                    >
                        <option value="sales">Sales Report</option>
                        <option value="transaction">Transaction Report</option>
                        <option value="inventory">Inventory Report</option>
                    </select>
                </div>

                <div id="reports-error" class="hidden rounded-xl border border-rose-500/35 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"></div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button
                        type="button"
                        id="btn-export-pdf"
                        class="inline-flex flex-1 items-center justify-center rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-white/90 shadow-sm hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Export PDF
                    </button>
                    <button
                        type="button"
                        id="btn-export-excel"
                        class="inline-flex flex-1 items-center justify-center rounded-xl bg-[#efe9df] px-4 py-3 text-sm font-semibold text-[#1c1c1c] shadow-sm hover:opacity-95 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Export Excel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var pdfUrl = @json(route('admin.reports.export.pdf'));
            var excelUrl = @json(route('admin.reports.export.excel'));
            var token = document.querySelector('meta[name="csrf-token"]');
            var csrf = token ? token.getAttribute('content') : '';
            var err = document.getElementById('reports-error');
            var form = document.getElementById('reports-export-form');
            var btnPdf = document.getElementById('btn-export-pdf');
            var btnXlsx = document.getElementById('btn-export-excel');

            function showErr(msg) {
                if (!err) return;
                err.textContent = msg;
                err.classList.remove('hidden');
            }
            function clearErr() {
                if (!err) return;
                err.textContent = '';
                err.classList.add('hidden');
            }

            function queryString() {
                var fd = new FormData(form);
                return new URLSearchParams(fd).toString();
            }

            function exportFile(url, btn) {
                clearErr();
                var qs = queryString();
                if (!qs) return;
                var start = document.getElementById('startDate').value;
                var end = document.getElementById('endDate').value;
                var type = document.getElementById('reportType').value;
                var slug = type === 'transaction' ? 'transaction' : type === 'inventory' ? 'inventory' : 'sales';
                var ext = url.indexOf('pdf') !== -1 ? 'pdf' : 'xlsx';
                var downloadName = 'khopi-kiki-' + slug + '-report-' + start + '-to-' + end + '.' + ext;
                btn.disabled = true;
                fetch(url + '?' + qs, {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json, application/pdf, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;q=0.9, */*;q=0.8',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                })
                    .then(function (res) {
                        var ct = res.headers.get('Content-Type') || '';
                        if (res.status === 422 && ct.indexOf('json') !== -1) {
                            return res.json().then(function (data) {
                                var msg = data.message || 'Validation failed.';
                                if (data.errors) {
                                    var keys = Object.keys(data.errors);
                                    if (keys.length && data.errors[keys[0]] && data.errors[keys[0]][0]) {
                                        msg = data.errors[keys[0]][0];
                                    }
                                }
                                throw new Error(msg);
                            });
                        }
                        if (!res.ok) {
                            throw new Error('Export failed (' + res.status + ').');
                        }
                        if (ct.indexOf('json') !== -1) {
                            return res.json().then(function () {
                                throw new Error('Unexpected response.');
                            });
                        }
                        return res.blob().then(function (blob) {
                            var a = document.createElement('a');
                            a.href = URL.createObjectURL(blob);
                            a.download = downloadName;
                            document.body.appendChild(a);
                            a.click();
                            a.remove();
                            URL.revokeObjectURL(a.href);
                        });
                    })
                    .catch(function (e) {
                        showErr(e.message || 'Export failed.');
                    })
                    .finally(function () {
                        btn.disabled = false;
                    });
            }

            btnPdf.addEventListener('click', function () {
                exportFile(pdfUrl, btnPdf);
            });
            btnXlsx.addEventListener('click', function () {
                exportFile(excelUrl, btnXlsx);
            });
        })();
    </script>
@endsection
