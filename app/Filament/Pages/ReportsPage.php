<?php

namespace App\Filament\Pages;

use App\Exports\CustomerReportExport;
use App\Exports\PaymentReportExport;
use App\Exports\SalesReportExport;
use App\Models\Order;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Monitoring';

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.pages.reports';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public array $summary = [];

    public array $popularPackages = [];

    public array $revenueByDay = [];

    public array $failedPayments = [];

    public array $customerRetention = [];

    public array $deviceUsage = [];

    public function mount(): void
    {
        $this->startDate = now()->subDays(30)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');

        $this->loadReports();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Date Range')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Start Date')
                            ->native(false)
                            ->required(),
                        DatePicker::make('endDate')
                            ->label('End Date')
                            ->native(false)
                            ->required(),
                    ])
                    ->columns(2),
            ])
            ->statePath(null);
    }

    public function loadReports(): void
    {
        $reportService = app(ReportService::class);
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        // Summary stats
        $ordersInRange = Order::whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end);

        $this->summary = [
            'total_orders' => (clone $ordersInRange)->count(),
            'total_revenue' => (clone $ordersInRange)->where('status', 'paid')->sum('amount'),
            'paid_orders' => (clone $ordersInRange)->where('status', 'paid')->count(),
            'failed_orders' => (clone $ordersInRange)->whereIn('status', ['failed', 'expired'])->count(),
            'pending_orders' => (clone $ordersInRange)->where('status', 'pending')->count(),
        ];

        // Popular packages
        $this->popularPackages = $reportService->popularPackages()->toArray();

        // Revenue by day
        $this->revenueByDay = $reportService->revenueByDay(30)->toArray();

        // Failed payments
        $this->failedPayments = $reportService->failedPayments()->toArray();

        // Customer retention
        $this->customerRetention = $reportService->customerRetention();

        // Device usage
        $this->deviceUsage = $reportService->deviceUsage()->toArray();
    }

    public function applyFilter(): void
    {
        $this->loadReports();

        Notification::make()
            ->title('Reports updated')
            ->success()
            ->send();
    }

    public function exportCsv(): BinaryFileResponse
    {
        return Excel::download(
            new SalesReportExport($this->startDate, $this->endDate),
            'sales-report-' . now()->format('Y-m-d') . '.csv'
        );
    }

    public function exportExcel(): BinaryFileResponse
    {
        return Excel::download(
            new SalesReportExport($this->startDate, $this->endDate),
            'sales-report-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportCustomersExcel(): BinaryFileResponse
    {
        return Excel::download(
            new CustomerReportExport($this->startDate, $this->endDate),
            'customer-report-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportPaymentsExcel(): BinaryFileResponse
    {
        return Excel::download(
            new PaymentReportExport($this->startDate, $this->endDate),
            'payment-report-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportPdf(): BinaryFileResponse
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        $orders = Order::with(['customer', 'package'])
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->orderByDesc('created_at')
            ->get();

        $pdf = Pdf::loadView('exports.sales-pdf', [
            'orders' => $orders,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'totalOrders' => $orders->count(),
            'totalRevenue' => $orders->where('status', 'paid')->sum('amount'),
            'paidOrders' => $orders->where('status', 'paid')->count(),
            'failedOrders' => $orders->whereIn('status', ['failed', 'expired'])->count(),
        ]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'sales-report-' . now()->format('Y-m-d') . '.pdf'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('apply')
                ->label('Apply Filter')
                ->action('applyFilter')
                ->color('primary')
                ->icon('heroicon-o-funnel'),
            Action::make('export_csv')
                ->label('Export CSV')
                ->action('exportCsv')
                ->color('gray')
                ->icon('heroicon-o-document-arrow-down'),
            Action::make('export_excel')
                ->label('Export Excel')
                ->action('exportExcel')
                ->color('success')
                ->icon('heroicon-o-table-cells'),
            Action::make('export_customers')
                ->label('Customers Excel')
                ->action('exportCustomersExcel')
                ->color('info')
                ->icon('heroicon-o-users'),
            Action::make('export_payments')
                ->label('Payments Excel')
                ->action('exportPaymentsExcel')
                ->color('warning')
                ->icon('heroicon-o-credit-card'),
            Action::make('export_pdf')
                ->label('Export PDF')
                ->action('exportPdf')
                ->color('danger')
                ->icon('heroicon-o-document-text'),
        ];
    }
}
