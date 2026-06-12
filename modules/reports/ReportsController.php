<?php

namespace Modules\Reports;

use Core\Auth;
use Core\Controller;
use Core\Helpers;
use Mpdf\Mpdf;

class ReportsController extends Controller
{
    private ReportsModel $model;

    public function __construct()
    {
        require_once __DIR__ . '/ReportsModel.php';
        $this->model = new ReportsModel();
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('reports', 'read');

        $this->view('reports/index', [
            'pageTitle' => 'Reports',
        ]);
    }

    public function sales(): void
    {
        $this->renderReport('sales', 'Sales Report', function ($from, $to, $filters) {
            return $this->model->salesReport(
                $from,
                $to,
                $filters['salesman_id'] ?? null,
                $filters['payment_method'] ?? null
            );
        }, ['salesman', 'payment']);
    }

    public function dailySummary(): void
    {
        $this->renderReport('daily_summary', 'Daily Summary', function ($from, $to) {
            return $this->model->dailySummary($from, $to);
        });
    }

    public function inventory(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('reports', 'read');

        $filters = $this->getFilters(false);
        $categoryId = (int) $this->input('category_id', 0) ?: null;
        $data = $this->model->inventoryReport($categoryId);

        $this->view('reports/inventory', [
            'pageTitle'  => 'Inventory Report',
            'reportKey'  => 'inventory',
            'data'       => $data,
            'filters'    => $filters,
            'filterOptions' => $this->model->getFilterOptions(),
            'categoryId' => $categoryId,
            'showDateFilter' => false,
        ]);
    }

    public function lowStock(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('reports', 'read');

        $data = $this->model->lowStockReport();

        $this->view('reports/low_stock', [
            'pageTitle'      => 'Low Stock Report',
            'reportKey'      => 'low_stock',
            'data'           => $data,
            'filters'        => $this->getFilters(false),
            'showDateFilter' => false,
        ]);
    }

    public function purchases(): void
    {
        $this->renderReport('purchases', 'Purchase Report', function ($from, $to, $filters) {
            return $this->model->purchaseReport($from, $to, $filters['supplier_id'] ?? null);
        }, ['supplier']);
    }

    public function profitLoss(): void
    {
        $this->renderReport('profit_loss', 'Profit & Loss', function ($from, $to) {
            return $this->model->profitLoss($from, $to);
        });
    }

    public function productSales(): void
    {
        $this->renderReport('product_sales', 'Product-wise Sales', function ($from, $to, $filters) {
            return $this->model->productWiseSales($from, $to, $filters['category_id'] ?? null);
        }, ['category']);
    }

    public function salesman(): void
    {
        $this->renderReport('salesman', 'Salesman Performance', function ($from, $to) {
            return $this->model->salesmanPerformance($from, $to);
        });
    }

    public function customerLedger(): void
    {
        $this->renderReport('customer_ledger', 'Customer Ledger', function ($from, $to, $filters) {
            return $this->model->customerLedgerReport($from, $to, $filters['customer_id'] ?? null);
        }, ['customer']);
    }

    public function discounts(): void
    {
        $this->renderReport('discounts', 'Discount Report', function ($from, $to) {
            return $this->model->discountReport($from, $to);
        });
    }

    public function activity(): void
    {
        $this->renderReport('activity', 'Activity Log Report', function ($from, $to, $filters) {
            return $this->model->activityLogReport(
                $from,
                $to,
                $filters['module'] ?? null,
                $filters['user_id'] ?? null
            );
        }, ['module', 'user']);
    }

    public function export(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('reports', 'export');

        $reportKey = (string) $this->input('report', '');
        $format = strtolower((string) $this->input('format', 'csv'));
        $filters = $this->getFilters(true);
        $extra = $this->collectExtraFilters();

        $result = $this->fetchReportData($reportKey, $filters['date_from'], $filters['date_to'], $extra);
        if ($result === null) {
            Auth::flash('error', 'Invalid report.');
            $this->redirect('reports');
        }

        $title = $result['title'];
        $data = $result['data'];
        $rows = $data['rows'] ?? [];

        if ($format === 'pdf') {
            $this->exportPdf($reportKey, $title, $rows, $data['summary'] ?? [], $filters);
            return;
        }

        $this->exportCsv($reportKey, $rows);
    }

    private function renderReport(string $view, string $title, callable $fetcher, array $extraFilters = []): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('reports', 'read');

        $filters = $this->getFilters(true);
        $extra = $this->collectExtraFilters();
        $data = $fetcher($filters['date_from'], $filters['date_to'], $extra);

        $this->view('reports/' . $view, [
            'pageTitle'       => $title,
            'reportKey'       => str_replace('-', '_', $view),
            'data'            => $data,
            'filters'         => $filters,
            'filterOptions'   => $this->model->getFilterOptions(),
            'extraFilters'    => $extra,
            'showDateFilter'  => true,
            'enabledFilters'  => $extraFilters,
        ]);
    }

    private function getFilters(bool $withDates): array
    {
        $defaults = Helpers::defaultDateRange();
        if (!$withDates) {
            return [
                'date_from'         => $defaults['from'],
                'date_to'           => $defaults['to'],
                'date_from_display' => $defaults['from_display'],
                'date_to_display'   => $defaults['to_display'],
            ];
        }

        $from = Helpers::parseFilterDate($this->input('date_from'), $defaults['from']);
        $to = Helpers::parseFilterDate($this->input('date_to'), $defaults['to']);

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [
            'date_from'         => $from,
            'date_to'           => $to,
            'date_from_display' => Helpers::formatDate($from),
            'date_to_display'   => Helpers::formatDate($to),
        ];
    }

    private function collectExtraFilters(): array
    {
        return [
            'salesman_id'    => (int) $this->input('salesman_id', 0) ?: null,
            'customer_id'    => (int) $this->input('customer_id', 0) ?: null,
            'supplier_id'    => (int) $this->input('supplier_id', 0) ?: null,
            'category_id'    => (int) $this->input('category_id', 0) ?: null,
            'user_id'        => (int) $this->input('user_id', 0) ?: null,
            'payment_method' => $this->input('payment_method') ?: null,
            'module'         => trim((string) $this->input('module', '')) ?: null,
        ];
    }

    private function fetchReportData(string $reportKey, string $from, string $to, array $extra): ?array
    {
        $map = [
            'sales'           => ['Sales Report', fn() => $this->model->salesReport($from, $to, $extra['salesman_id'], $extra['payment_method'])],
            'daily_summary'   => ['Daily Summary', fn() => $this->model->dailySummary($from, $to)],
            'inventory'       => ['Inventory Report', fn() => $this->model->inventoryReport($extra['category_id'])],
            'low_stock'       => ['Low Stock Report', fn() => $this->model->lowStockReport()],
            'purchases'       => ['Purchase Report', fn() => $this->model->purchaseReport($from, $to, $extra['supplier_id'])],
            'profit_loss'     => ['Profit & Loss', fn() => $this->model->profitLoss($from, $to)],
            'product_sales'   => ['Product-wise Sales', fn() => $this->model->productWiseSales($from, $to, $extra['category_id'])],
            'salesman'        => ['Salesman Performance', fn() => $this->model->salesmanPerformance($from, $to)],
            'customer_ledger' => ['Customer Ledger', fn() => $this->model->customerLedgerReport($from, $to, $extra['customer_id'])],
            'discounts'       => ['Discount Report', fn() => $this->model->discountReport($from, $to)],
            'activity'        => ['Activity Log', fn() => $this->model->activityLogReport($from, $to, $extra['module'], $extra['user_id'])],
        ];

        if (!isset($map[$reportKey])) {
            return null;
        }

        return ['title' => $map[$reportKey][0], 'data' => $map[$reportKey][1]()];
    }

    private function exportCsv(string $reportKey, array $rows): void
    {
        $filename = $reportKey . '-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if (!$rows) {
            fputcsv($out, ['No data']);
            fclose($out);
            exit;
        }

        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    private function exportPdf(string $reportKey, string $title, array $rows, array $summary, array $filters): void
    {
        $html = '<h2>' . htmlspecialchars($title) . '</h2>';
        $html .= '<p>Period: ' . htmlspecialchars($filters['date_from_display']) . ' to ' . htmlspecialchars($filters['date_to_display']) . '</p>';

        if ($summary) {
            $html .= '<table border="1" cellpadding="4" cellspacing="0" width="100%" style="margin-bottom:15px;"><tr>';
            foreach ($summary as $k => $v) {
                $html .= '<th>' . htmlspecialchars(str_replace('_', ' ', ucfirst($k))) . '</th>';
            }
            $html .= '</tr><tr>';
            foreach ($summary as $v) {
                $html .= '<td>' . htmlspecialchars(is_numeric($v) ? number_format((float) $v, 2) : (string) $v) . '</td>';
            }
            $html .= '</tr></table>';
        }

        if ($rows) {
            $html .= '<table border="1" cellpadding="4" cellspacing="0" width="100%"><tr>';
            foreach (array_keys($rows[0]) as $col) {
                $html .= '<th>' . htmlspecialchars(str_replace('_', ' ', ucfirst($col))) . '</th>';
            }
            $html .= '</tr>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $val) {
                    $html .= '<td>' . htmlspecialchars((string) $val) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
        } else {
            $html .= '<p>No detailed rows for this report.</p>';
        }

        $mpdf = new Mpdf(['format' => 'A4-L']);
        $mpdf->WriteHTML($html);
        $mpdf->Output($reportKey . '-' . date('Y-m-d') . '.pdf', 'D');
        exit;
    }
}
