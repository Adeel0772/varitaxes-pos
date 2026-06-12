<?php

namespace Modules\Purchases;

use Core\Auth;
use Core\Controller;
use Core\Helpers;

class PurchasesController extends Controller
{
    private PurchasesModel $model;

    public function __construct()
    {
        require_once __DIR__ . '/PurchasesModel.php';
        require_once dirname(__DIR__) . '/suppliers/SuppliersModel.php';
        $this->model = new PurchasesModel();
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('purchases', 'read');

        $supplierId = (int) $this->input('supplier_id', 0) ?: null;
        $search = trim((string) $this->input('search', ''));
        $page = max(1, (int) $this->input('page', 1));
        $sort = (string) $this->input('sort', 'purchase_date');
        $dir = (string) $this->input('dir', 'desc');

        $dateFrom = Helpers::parseFilterDate($this->input('date_from'));
        $dateTo = Helpers::parseFilterDate($this->input('date_to'));

        $purchases = $this->model->getAll($supplierId, $dateFrom, $dateTo, $search, $page, $sort, $dir);

        $suppliersModel = new \Modules\Suppliers\SuppliersModel();

        $this->view('purchases/index', [
            'pageTitle'  => 'Purchases',
            'purchases'  => $purchases,
            'suppliers'  => $suppliersModel->getAllActive(),
            'supplierId' => $supplierId,
            'search'     => $search,
            'dateFrom'   => $dateFrom ? Helpers::formatDate($dateFrom) : '',
            'dateTo'     => $dateTo ? Helpers::formatDate($dateTo) : '',
            'sort'       => $sort,
            'dir'        => $dir,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('purchases', 'create');

        $suppliersModel = new \Modules\Suppliers\SuppliersModel();

        $this->view('purchases/create', [
            'pageTitle' => 'New Purchase',
            'suppliers' => $suppliersModel->getAllActive(),
            'old'       => ['purchase_date' => date('Y-m-d')],
            'items'     => [],
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('purchases', 'create');
        $this->requirePost();
        $this->verifyCsrf();

        $data = $this->collectPurchaseData();
        $items = $this->collectItems();
        $errors = $this->validatePurchase($data, $items);

        $suppliersModel = new \Modules\Suppliers\SuppliersModel();

        if ($errors) {
            $this->view('purchases/create', [
                'pageTitle' => 'New Purchase',
                'suppliers' => $suppliersModel->getAllActive(),
                'old'       => $data,
                'items'     => $items,
                'errors'    => $errors,
            ]);
            return;
        }

        try {
            $purchaseId = $this->model->create($data, $items);
            $this->logActivity('create', 'purchases', $purchaseId, 'Purchase recorded for supplier #' . $data['supplier_id']);
            Auth::flash('success', 'Purchase recorded successfully.');
            $this->redirect('purchases/view?id=' . $purchaseId);
        } catch (\Exception $e) {
            Auth::flash('error', 'Failed to record purchase. Please try again.');
            $this->redirect('purchases/create');
        }
    }

    public function show(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('purchases', 'read');

        $id = (int) $this->input('id', 0);
        $purchase = $this->model->findWithItems($id);
        if (!$purchase) {
            Auth::flash('error', 'Purchase not found.');
            $this->redirect('purchases');
        }

        $this->view('purchases/view', [
            'pageTitle' => 'Purchase Details',
            'purchase'  => $purchase,
            'canEdit'   => $this->model->canEdit($purchase) && Auth::can('purchases', 'update'),
        ]);
    }

    public function edit(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('purchases', 'update');

        $id = (int) $this->input('id', 0);
        $purchase = $this->model->findWithItems($id);
        if (!$purchase) {
            Auth::flash('error', 'Purchase not found.');
            $this->redirect('purchases');
        }

        if (!$this->model->canEdit($purchase)) {
            Auth::flash('error', 'Purchases can only be edited on the same day they were recorded.');
            $this->redirect('purchases/view?id=' . $id);
        }

        $suppliersModel = new \Modules\Suppliers\SuppliersModel();

        $this->view('purchases/edit', [
            'pageTitle' => 'Edit Purchase',
            'purchase'  => $purchase,
            'suppliers' => $suppliersModel->getAllActive(),
        ]);
    }

    public function update(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('purchases', 'update');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $purchase = $this->model->findWithItems($id);
        if (!$purchase) {
            Auth::flash('error', 'Purchase not found.');
            $this->redirect('purchases');
        }

        if (!$this->model->canEdit($purchase)) {
            Auth::flash('error', 'Purchases can only be edited on the same day they were recorded.');
            $this->redirect('purchases/view?id=' . $id);
        }

        $data = $this->collectPurchaseData();
        $items = $this->collectItems();
        $errors = $this->validatePurchase($data, $items);

        $suppliersModel = new \Modules\Suppliers\SuppliersModel();

        if ($errors) {
            $purchase = array_merge($purchase, $data);
            $purchase['items'] = $this->formatItemsForView($items);
            $this->view('purchases/edit', [
                'pageTitle' => 'Edit Purchase',
                'purchase'  => $purchase,
                'suppliers' => $suppliersModel->getAllActive(),
                'errors'    => $errors,
            ]);
            return;
        }

        try {
            if ($this->model->updatePurchase($id, $data, $items)) {
                $this->logActivity('update', 'purchases', $id, 'Purchase updated');
                Auth::flash('success', 'Purchase updated successfully.');
                $this->redirect('purchases/view?id=' . $id);
            }
        } catch (\Exception $e) {
            Auth::flash('error', $e->getMessage() ?: 'Failed to update purchase.');
            $this->redirect('purchases/edit?id=' . $id);
        }

        Auth::flash('error', 'Failed to update purchase.');
        $this->redirect('purchases/edit?id=' . $id);
    }

    private function collectPurchaseData(): array
    {
        $dateInput = trim((string) $this->input('purchase_date', date('Y-m-d')));
        $parsed = Helpers::parseFilterDate($dateInput, date('Y-m-d'));

        return [
            'supplier_id'   => (int) $this->input('supplier_id', 0),
            'purchase_date' => $parsed,
            'notes'         => trim($this->input('notes', '')),
        ];
    }

    private function collectItems(): array
    {
        $raw = $_POST['items'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $productId = (int) ($row['product_id'] ?? 0);
            $qty = (int) ($row['qty'] ?? 0);
            $price = (float) ($row['purchase_price'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }
            $items[] = [
                'product_id'     => $productId,
                'variant_id'     => !empty($row['variant_id']) ? (int) $row['variant_id'] : null,
                'product_name'   => trim((string) ($row['product_name'] ?? '')),
                'product_code'   => trim((string) ($row['product_code'] ?? '')),
                'qty'            => $qty,
                'purchase_price' => $price,
            ];
        }
        return $items;
    }

    private function formatItemsForView(array $items): array
    {
        return array_map(function ($item) {
            return [
                'product_id'     => $item['product_id'],
                'variant_id'     => $item['variant_id'] ?? null,
                'product_name'   => $item['product_name'] ?? '',
                'product_code'   => $item['product_code'] ?? '',
                'qty'            => $item['qty'],
                'purchase_price' => $item['purchase_price'],
            ];
        }, $items);
    }

    private function validatePurchase(array $data, array $items): array
    {
        $errors = [];

        if ($data['supplier_id'] <= 0) {
            $errors[] = 'Please select a supplier.';
        }
        if ($data['purchase_date'] === '') {
            $errors[] = 'Purchase date is required.';
        }
        if (empty($items)) {
            $errors[] = 'Add at least one product line.';
        }

        foreach ($items as $i => $item) {
            $line = $i + 1;
            if ($item['purchase_price'] < 0) {
                $errors[] = "Line {$line}: purchase price cannot be negative.";
            }
        }

        return $errors;
    }
}
