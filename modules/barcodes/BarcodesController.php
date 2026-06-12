<?php

namespace Modules\Barcodes;

use Core\Auth;
use Core\Controller;
use Core\Helpers;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodesController extends Controller
{
    private BarcodesModel $model;

    public function __construct()
    {
        require_once __DIR__ . '/BarcodesModel.php';
        $this->model = new BarcodesModel();
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('barcodes', 'read');

        require_once dirname(__DIR__) . '/products/CategoriesModel.php';
        $categoriesModel = new \Modules\Products\CategoriesModel();

        $search = trim((string) $this->input('search', ''));
        $categoryId = (int) $this->input('category_id', 0) ?: null;
        $page = max(1, (int) $this->input('page', 1));

        $products = $this->model->getProductsForSelection($search, $categoryId, $page);

        $this->view('barcodes/index', [
            'pageTitle'  => 'Barcode Labels',
            'products'   => $products,
            'search'     => $search,
            'categoryId' => $categoryId,
            'categories' => $categoriesModel->getAllActive(),
        ]);
    }

    public function print(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('barcodes', 'print');

        $productIds = $_POST['product_ids'] ?? $_GET['ids'] ?? [];
        if (is_string($productIds)) {
            $productIds = array_filter(explode(',', $productIds));
        }
        if (!is_array($productIds)) {
            $productIds = [];
        }

        $quantities = $_POST['quantities'] ?? [];
        $labelSize = in_array($this->input('label_size', 'medium'), ['small', 'medium', 'large'], true)
            ? $this->input('label_size', 'medium')
            : 'medium';
        $showPrice = (bool) ($this->input('show_price', '1'));

        $products = $this->model->getProductsByIds($productIds);
        if (!$products) {
            Auth::flash('error', 'Select at least one product.');
            $this->redirect('barcodes');
        }

        $labels = [];
        foreach ($products as $product) {
            $qty = max(1, (int) ($quantities[$product['id']] ?? 1));
            $barcode = $product['barcode'] ?: Helpers::generateBarcode((int) $product['id'], (int) Auth::tenantId());
            for ($i = 0; $i < $qty; $i++) {
                $labels[] = [
                    'product' => $product,
                    'barcode' => $barcode,
                ];
            }
        }

        $this->logActivity('print', 'barcodes', null, count($labels) . ' labels printed');

        $this->view('barcodes/print', [
            'pageTitle' => 'Print Barcodes',
            'labels'    => $labels,
            'labelSize' => $labelSize,
            'showPrice' => $showPrice,
        ], 'print');
    }

    public function image(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('barcodes', 'read');

        $code = trim((string) $this->input('code', ''));
        $productId = (int) $this->input('product_id', 0);

        if (!$code && $productId) {
            $product = $this->model->findProductBarcode($productId);
            if ($product) {
                $code = $product['barcode'] ?: Helpers::generateBarcode($productId, (int) Auth::tenantId());
            }
        }

        if ($code === '') {
            http_response_code(400);
            exit('Barcode code required');
        }

        $generator = new BarcodeGeneratorPNG();
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        echo $generator->getBarcode($code, $generator::TYPE_CODE_128, 2, 60);
        exit;
    }
}
