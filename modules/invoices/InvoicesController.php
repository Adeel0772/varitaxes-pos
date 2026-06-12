<?php

namespace Modules\Invoices;

use Core\Auth;
use Core\Controller;
use Mpdf\Mpdf;

class InvoicesController extends Controller
{
    private InvoicesModel $model;

    public function __construct()
    {
        require_once __DIR__ . '/InvoicesModel.php';
        $this->model = new InvoicesModel();
    }

    public function print(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('invoices', 'print');

        $saleId = (int) $this->input('sale_id', 0);
        if (!$saleId) {
            Auth::flash('error', 'Sale ID is required.');
            $this->redirect('sales');
        }

        $data = $this->model->getSaleForInvoice($saleId);
        if (!$data) {
            Auth::flash('error', 'Sale not found.');
            $this->redirect('sales');
        }

        $this->model->markInvoicePrinted($saleId);
        $this->logActivity('print', 'invoices', $saleId, 'Invoice printed');

        $format = $data['shop']['invoice_format'] ?? 'a4';
        $view = $format === 'carbon' ? 'invoices/thermal' : 'invoices/a4';
        $printClass = $format === 'carbon' ? 'invoice-thermal-wrap' : 'invoice-a4-wrap';

        $this->view($view, [
            'pageTitle'  => 'Invoice ' . $data['sale']['sale_number'],
            'invoice'    => $data,
            'printClass' => $printClass,
        ], 'print');
    }

    public function pdf(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('invoices', 'print');

        $saleId = (int) $this->input('sale_id', 0);
        if (!$saleId) {
            Auth::flash('error', 'Sale ID is required.');
            $this->redirect('sales');
        }

        $data = $this->model->getSaleForInvoice($saleId);
        if (!$data) {
            Auth::flash('error', 'Sale not found.');
            $this->redirect('sales');
        }

        $this->model->markInvoicePrinted($saleId);
        $this->logActivity('export', 'invoices', $saleId, 'Invoice PDF downloaded');

        $format = $data['shop']['invoice_format'] ?? 'a4';
        $view = $format === 'carbon' ? 'invoices/thermal' : 'invoices/a4';

        ob_start();
        extract(['invoice' => $data, 'forPdf' => true]);
        require dirname(__DIR__, 2) . '/views/' . $view . '.php';
        $html = ob_get_clean();

        $mpdfConfig = $format === 'carbon'
            ? ['format' => [80, 297], 'margin_left' => 2, 'margin_right' => 2, 'margin_top' => 5, 'margin_bottom' => 5]
            : ['format' => 'A4', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 10];

        $mpdf = new Mpdf($mpdfConfig);
        $mpdf->WriteHTML($html);
        $filename = 'invoice-' . preg_replace('/[^a-zA-Z0-9\-]/', '', $data['sale']['sale_number']) . '.pdf';
        $mpdf->Output($filename, 'D');
        exit;
    }
}
