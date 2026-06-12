<?php

namespace Modules\Products;

use Core\Auth;
use Core\Controller;
use Core\Helpers;

class ProductsController extends Controller
{
    private ProductsModel $model;
    private CategoriesModel $categoriesModel;
    private BrandsModel $brandsModel;

    public function __construct()
    {
        require_once __DIR__ . '/ProductsModel.php';
        require_once __DIR__ . '/CategoriesModel.php';
        require_once __DIR__ . '/BrandsModel.php';
        $this->model = new ProductsModel();
        $this->categoriesModel = new CategoriesModel();
        $this->brandsModel = new BrandsModel();
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('products', 'read');

        $search = trim((string) $this->input('search', ''));
        $categoryId = (int) $this->input('category_id', 0) ?: null;
        $brandId = (int) $this->input('brand_id', 0) ?: null;
        $status = $this->input('status', '');
        $page = max(1, (int) $this->input('page', 1));
        $sort = (string) $this->input('sort', 'created_at');
        $dir = (string) $this->input('dir', 'desc');

        $products = $this->model->getAll($search, $categoryId, $brandId, $status ?: null, $page, $sort, $dir);

        $this->view('products/index', [
            'pageTitle'  => 'Products',
            'products'   => $products,
            'search'     => $search,
            'categoryId' => $categoryId,
            'brandId'    => $brandId,
            'status'     => $status,
            'sort'       => $sort,
            'dir'        => $dir,
            'categories' => $this->categoriesModel->getAllActive(),
            'brands'     => $this->brandsModel->getAllActive(),
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('products', 'create');

        $this->view('products/create', [
            'pageTitle'  => 'Add Product',
            'categories' => $this->categoriesModel->getAllActive(),
            'brands'     => $this->brandsModel->getAllActive(),
            'old'        => [],
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('products', 'create');
        $this->requirePost();
        $this->verifyCsrf();

        $data = $this->collectProductData();
        $errors = $this->validateProduct($data);

        if (!empty($_FILES['image']['name'])) {
            $imageErrors = Helpers::validateImage($_FILES['image']);
            if ($imageErrors) {
                $errors = array_merge($errors, $imageErrors);
            }
        }

        if ($errors) {
            $this->view('products/create', [
                'pageTitle'  => 'Add Product',
                'categories' => $this->categoriesModel->getAllActive(),
                'brands'     => $this->brandsModel->getAllActive(),
                'old'        => $data,
                'errors'     => $errors,
            ]);
            return;
        }

        if (!empty($_FILES['image']['name'])) {
            $data['image'] = Helpers::uploadImage($_FILES['image'], 'products');
            if (!$data['image']) {
                $this->view('products/create', [
                    'pageTitle'  => 'Add Product',
                    'categories' => $this->categoriesModel->getAllActive(),
                    'brands'     => $this->brandsModel->getAllActive(),
                    'old'        => $data,
                    'errors'     => ['Failed to upload image.'],
                ]);
                return;
            }
        }

        if ($data['product_code'] === '') {
            $categoryCode = $data['category_id']
                ? $this->categoriesModel->getCodeById((int) $data['category_id'])
                : null;
            $data['product_code'] = Helpers::generateProductCode(
                $categoryCode,
                $data['product_type'],
                $data['size'],
                $data['origin'],
                (int) Auth::tenantId(),
                \Core\Database::getInstance()
            );
        }

        try {
            $productId = $this->model->create($data);
            $this->logActivity('create', 'products', $productId, 'Product created: ' . $data['name']);
            Auth::flash('success', 'Product created successfully.');
            $this->redirect('products/view?id=' . $productId);
        } catch (\Exception $e) {
            Auth::flash('error', 'Failed to create product. Please try again.');
            $this->redirect('products/create');
        }
    }

    public function edit(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('products', 'update');

        $id = (int) $this->input('id', 0);
        $product = $this->model->findWithDetails($id);
        if (!$product) {
            Auth::flash('error', 'Product not found.');
            $this->redirect('products');
        }

        $this->view('products/edit', [
            'pageTitle'  => 'Edit Product',
            'product'    => $product,
            'categories' => $this->categoriesModel->getAllActive(),
            'brands'     => $this->brandsModel->getAllActive(),
        ]);
    }

    public function update(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('products', 'update');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $product = $this->model->findWithDetails($id);
        if (!$product) {
            Auth::flash('error', 'Product not found.');
            $this->redirect('products');
        }

        $data = $this->collectProductData();
        $data['image'] = $product['image'];
        $errors = $this->validateProduct($data, $id);

        if (!empty($_FILES['image']['name'])) {
            $imageErrors = Helpers::validateImage($_FILES['image']);
            if ($imageErrors) {
                $errors = array_merge($errors, $imageErrors);
            }
        }

        if ($errors) {
            $this->view('products/edit', [
                'pageTitle'  => 'Edit Product',
                'product'    => array_merge($product, $data),
                'categories' => $this->categoriesModel->getAllActive(),
                'brands'     => $this->brandsModel->getAllActive(),
                'errors'     => $errors,
            ]);
            return;
        }

        if (!empty($_FILES['image']['name'])) {
            $uploaded = Helpers::uploadImage($_FILES['image'], 'products');
            if (!$uploaded) {
                $this->view('products/edit', [
                    'pageTitle'  => 'Edit Product',
                    'product'    => array_merge($product, $data),
                    'categories' => $this->categoriesModel->getAllActive(),
                    'brands'     => $this->brandsModel->getAllActive(),
                    'errors'     => ['Failed to upload image.'],
                ]);
                return;
            }
            $data['image'] = $uploaded;
        }

        $data['low_stock_threshold'] = (int) $this->input('low_stock_threshold', $product['low_stock_threshold'] ?? 5);

        if ($this->model->update($id, $data)) {
            $this->logActivity('update', 'products', $id, 'Product updated: ' . $data['name']);
            Auth::flash('success', 'Product updated successfully.');
            $this->redirect('products/view?id=' . $id);
        }

        Auth::flash('error', 'Failed to update product.');
        $this->redirect('products/edit?id=' . $id);
    }

    public function delete(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('products', 'delete');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $product = $this->model->find($id);
        if (!$product) {
            Auth::flash('error', 'Product not found.');
            $this->redirect('products');
        }

        $this->model->softDelete($id);
        $this->logActivity('delete', 'products', $id, 'Product deleted: ' . $product['name']);
        Auth::flash('success', 'Product deleted successfully.');
        $this->redirect('products');
    }

    public function show(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('products', 'read');

        $id = (int) $this->input('id', 0);
        $product = $this->model->findWithDetails($id);
        if (!$product) {
            Auth::flash('error', 'Product not found.');
            $this->redirect('products');
        }

        $this->view('products/view', [
            'pageTitle'    => 'Product Details',
            'product'      => $product,
            'stockHistory' => $this->model->getStockHistory($id),
        ]);
    }

    public function toggleStatus(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('products', 'update');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $newStatus = $this->model->toggleStatus($id);

        if ($newStatus === null) {
            if (Auth::isAjax()) {
                $this->json(['success' => false, 'message' => 'Product not found.'], 404);
            }
            Auth::flash('error', 'Product not found.');
            $this->redirect('products');
        }

        $this->logActivity('update', 'products', $id, 'Status changed to: ' . $newStatus);

        if (Auth::isAjax()) {
            $this->json(['success' => true, 'status' => $newStatus]);
        }

        Auth::flash('success', 'Product status updated to ' . $newStatus . '.');
        $redirect = $this->input('redirect', 'products');
        $this->redirect($redirect);
    }

    public function generateCode(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('products', 'create');

        $categoryId = (int) $this->input('category_id', 0);
        $categoryCode = $categoryId ? $this->categoriesModel->getCodeById($categoryId) : null;
        $productType = trim((string) $this->input('product_type', ''));
        $size = trim((string) $this->input('size', ''));
        $origin = trim((string) $this->input('origin', ''));

        $code = Helpers::generateProductCode(
            $categoryCode,
            $productType ?: null,
            $size ?: null,
            $origin ?: null,
            (int) Auth::tenantId(),
            \Core\Database::getInstance()
        );

        $this->json([
            'success'      => true,
            'product_code' => $code,
            'category_code'=> $categoryCode,
        ]);
    }

    public function search(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();

        if (!Auth::can('products', 'read') && !Auth::can('sales', 'create')) {
            $this->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $q = trim((string) $this->input('q', ''));
        $products = $this->model->searchForPos($q);

        $showPurchasePrice = Auth::canSeePurchasePrice();

        $this->json([
            'success'  => true,
            'products' => array_map(function ($p) use ($showPurchasePrice) {
                $row = [
                    'id'             => (int) $p['id'],
                    'name'           => $p['name'],
                    'product_code'   => $p['product_code'],
                    'barcode'        => $p['barcode'],
                    'sale_price'     => (float) $p['sale_price'],
                    'min_sale_price' => (float) $p['min_sale_price'],
                    'qty_in_stock'   => (int) $p['qty_in_stock'],
                    'image'          => !empty($p['image']) ? Helpers::productImageUrl((int) $p['id']) : null,
                    'product_type'   => $p['product_type'],
                    'size'           => $p['size'],
                    'color'          => $p['color'],
                    'has_variants'   => (int) ($p['variant_count'] ?? 0) > 0,
                ];
                if ($showPurchasePrice) {
                    $row['purchase_price'] = (float) ($p['purchase_price'] ?? 0);
                }
                return $row;
            }, $products),
        ]);
    }

    public function image(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();

        $id = (int) $this->input('id', 0);
        $product = $this->model->find($id);

        if (!$product || empty($product['image'])) {
            http_response_code(404);
            exit;
        }

        Helpers::serveUploadFile($product['image']);
    }

    private function collectProductData(): array
    {
        return [
            'name'                => trim($this->input('name', '')),
            'product_code'        => trim($this->input('product_code', '')),
            'category_id'         => (int) $this->input('category_id', 0) ?: null,
            'brand_id'            => (int) $this->input('brand_id', 0) ?: null,
            'product_type'        => trim($this->input('product_type', '')),
            'size'                => trim($this->input('size', '')),
            'color'               => trim($this->input('color', '')),
            'origin'              => trim($this->input('origin', '')),
            'description'         => trim($this->input('description', '')),
            'purchase_price'      => (float) $this->input('purchase_price', 0),
            'sale_price'          => (float) $this->input('sale_price', 0),
            'min_sale_price'      => (float) $this->input('min_sale_price', 0),
            'barcode'             => trim($this->input('barcode', '')),
            'status'              => $this->input('status', 'active') === 'inactive' ? 'inactive' : 'active',
            'initial_stock'       => (int) $this->input('initial_stock', 0),
            'low_stock_threshold' => (int) $this->input('low_stock_threshold', 5),
            'image'               => null,
        ];
    }

    private function validateProduct(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Product name is required.';
        }
        if ($data['sale_price'] <= 0) {
            $errors[] = 'Sale price must be greater than zero.';
        }
        if ($data['min_sale_price'] > 0 && $data['min_sale_price'] > $data['sale_price']) {
            $errors[] = 'Minimum sale price cannot exceed sale price.';
        }
        if ($data['product_code'] !== '' && $this->model->codeExists($data['product_code'], $excludeId)) {
            $errors[] = 'Product code already exists.';
        }
        if ($data['barcode'] !== '' && $this->model->barcodeExists($data['barcode'], $excludeId)) {
            $errors[] = 'Barcode already exists.';
        }
        if (!$excludeId && $data['initial_stock'] < 0) {
            $errors[] = 'Initial stock cannot be negative.';
        }

        return $errors;
    }
}
