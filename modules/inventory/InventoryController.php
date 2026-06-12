<?php

namespace Modules\Inventory;

use Core\Auth;
use Core\Controller;

class InventoryController extends Controller
{
    private InventoryModel $model;

    public function __construct()
    {
        require_once __DIR__ . '/InventoryModel.php';
        $this->model = new InventoryModel();
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('inventory', 'read');

        $search = trim((string) $this->input('search', ''));
        $stockFilter = $this->input('stock', '');
        $page = max(1, (int) $this->input('page', 1));
        $sort = (string) $this->input('sort', 'name');
        $dir = (string) $this->input('dir', 'asc');

        $filter = in_array($stockFilter, ['low', 'out'], true) ? $stockFilter : null;
        $inventory = $this->model->getAll($search, $filter, $page, $sort, $dir);
        $lowStockCount = count($this->model->getLowStock(500));

        $this->view('inventory/index', [
            'pageTitle'    => 'Inventory',
            'inventory'    => $inventory,
            'search'       => $search,
            'stockFilter'  => $stockFilter,
            'sort'         => $sort,
            'dir'          => $dir,
            'lowStockCount'=> $lowStockCount,
        ]);
    }

    public function adjust(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('inventory', 'adjust');
        $this->requirePost();
        $this->verifyCsrf();

        $productId = (int) $this->input('product_id', 0);
        $variantId = (int) $this->input('variant_id', 0) ?: null;
        $qty = (int) $this->input('qty', 0);
        $reason = trim((string) $this->input('reason', ''));

        $product = $this->model->findProduct($productId);
        if (!$product) {
            if (Auth::isAjax()) {
                $this->json(['success' => false, 'message' => 'Product not found.'], 404);
            }
            Auth::flash('error', 'Product not found.');
            $this->redirect('inventory');
        }

        if ($qty === 0) {
            $msg = 'Adjustment quantity cannot be zero.';
            if (Auth::isAjax()) {
                $this->json(['success' => false, 'message' => $msg], 422);
            }
            Auth::flash('error', $msg);
            $this->redirect('inventory');
        }

        if ($reason === '') {
            $msg = 'Please provide a reason for the adjustment.';
            if (Auth::isAjax()) {
                $this->json(['success' => false, 'message' => $msg], 422);
            }
            Auth::flash('error', $msg);
            $this->redirect('inventory');
        }

        try {
            $this->model->adjustStock($productId, $variantId, $qty, $reason, (int) Auth::userId());
            $this->logActivity('adjust', 'inventory', $productId, "Stock adjusted by {$qty}: {$reason}");

            if (Auth::isAjax()) {
                $updated = $this->model->findProduct($productId);
                $this->json([
                    'success'      => true,
                    'message'      => 'Stock adjusted successfully.',
                    'qty_in_stock' => (int) ($updated['qty_in_stock'] ?? 0),
                ]);
            }

            Auth::flash('success', 'Stock adjusted successfully.');
        } catch (\Exception $e) {
            $msg = $e->getMessage() ?: 'Failed to adjust stock.';
            if (Auth::isAjax()) {
                $this->json(['success' => false, 'message' => $msg], 422);
            }
            Auth::flash('error', $msg);
        }

        $this->redirect('inventory');
    }

    public function history(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('inventory', 'read');

        $productId = (int) $this->input('product_id', 0);
        $page = max(1, (int) $this->input('page', 1));

        $product = $productId ? $this->model->findProduct($productId) : null;
        $history = $productId ? $this->model->getHistory($productId, $page) : ['data' => [], 'total' => 0, 'current_page' => 1, 'last_page' => 1, 'per_page' => PER_PAGE];

        $this->view('inventory/history', [
            'pageTitle' => $product ? 'Stock History — ' . $product['name'] : 'Stock History',
            'product'   => $product,
            'history'   => $history,
            'productId' => $productId,
        ]);
    }

    public function lowStock(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('inventory', 'read');

        $items = $this->model->getLowStock(500);

        $this->view('inventory/low_stock', [
            'pageTitle' => 'Low Stock Alert',
            'items'     => $items,
        ]);
    }
}
