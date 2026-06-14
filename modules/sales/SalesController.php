<?php

namespace Modules\Sales;

use Core\Auth;
use Core\Controller;
use Core\Helpers;
use Modules\Products\ProductsModel;

class SalesController extends Controller
{
    private SalesModel $model;
    private ProductsModel $productsModel;

    public function __construct()
    {
        require_once __DIR__ . '/SalesModel.php';
        require_once dirname(__DIR__) . '/products/ProductsModel.php';
        require_once dirname(__DIR__) . '/settings/SettingsModel.php';
        $this->model = new SalesModel();
        $this->productsModel = new ProductsModel();
    }

    public function pos(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('sales', 'create');

        $settingsModel = new \Modules\Settings\SettingsModel();
        $settings = $settingsModel->getAllSettings();

        $this->view('sales/pos', [
            'pageTitle'       => 'POS Sale',
            'defaultPayment'  => $settings['default_payment_method'] ?? 'cash',
            'currencySymbol'  => Helpers::getCurrencySymbol(),
        ]);
    }

    public function complete(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('sales', 'create');
        $this->requirePost();
        $this->verifyCsrf();

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        try {
            $result = $this->model->completeSale($payload);
            $this->logActivity('create', 'sales', $result['id'], 'Sale completed: ' . $result['sale_number']);

            $this->json([
                'success'      => true,
                'message'      => 'Sale completed successfully.',
                'sale_id'      => $result['id'],
                'sale_number'  => $result['sale_number'],
                'total_amount' => $result['total_amount'],
                'change_amount'=> $result['change_amount'],
                'redirect'     => Auth::baseUrl('sales/success?id=' . $result['id']),
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to complete sale. Please try again.'], 500);
        }
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('sales', 'read');

        $search = trim((string) $this->input('search', ''));
        $dateFrom = trim((string) $this->input('date_from', ''));
        $dateTo = trim((string) $this->input('date_to', ''));
        $page = max(1, (int) $this->input('page', 1));

        $salesmanId = null;
        if (Auth::role() === 'salesman') {
            $salesmanId = (int) Auth::userId();
        }

        $sales = $this->model->getAll(
            $search,
            $dateFrom ?: null,
            $dateTo ?: null,
            $salesmanId,
            $page
        );

        $this->view('sales/index', [
            'pageTitle' => 'Sales',
            'sales'     => $sales,
            'search'    => $search,
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
            'isSalesman'=> Auth::role() === 'salesman',
        ]);
    }

    public function show(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('sales', 'read');

        $id = (int) $this->input('id', 0);
        $sale = $this->model->findWithItems($id);

        if (!$sale) {
            Auth::flash('error', 'Sale not found.');
            $this->redirect('sales');
        }

        if (Auth::role() === 'salesman' && (int) $sale['salesman_id'] !== Auth::userId()) {
            Auth::flash('error', 'You can only view your own sales.');
            $this->redirect('sales');
        }

        $this->view('sales/view', [
            'pageTitle'      => 'Sale ' . $sale['sale_number'],
            'sale'           => $sale,
            'showPurchase'   => Auth::canSeePurchasePrice(),
        ]);
    }

    public function success(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('sales', 'create');

        $id = (int) $this->input('id', 0);
        $sale = $this->model->findWithItems($id);

        if (!$sale) {
            Auth::flash('error', 'Sale not found.');
            $this->redirect('sales/pos');
        }

        if ((int) $sale['salesman_id'] !== Auth::userId() && Auth::role() === 'salesman') {
            Auth::flash('error', 'Sale not found.');
            $this->redirect('sales/pos');
        }

        $settingsModel = new \Modules\Settings\SettingsModel();
        $settings = $settingsModel->getAllSettings();

        $this->view('sales/success', [
            'pageTitle'      => 'Sale Complete',
            'sale'           => $sale,
            'currencySymbol' => Helpers::getCurrencySymbol(),
        ]);
    }

    public function myToday(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('sales', 'read');

        $sales = $this->model->getMyTodaySales((int) Auth::userId());
        $total = array_sum(array_column($sales, 'total_amount'));

        $this->view('sales/index', [
            'pageTitle'  => "My Today's Sales",
            'sales'      => [
                'data'         => $sales,
                'total'        => count($sales),
                'per_page'     => count($sales) ?: 1,
                'current_page' => 1,
                'last_page'    => 1,
            ],
            'search'     => '',
            'dateFrom'   => date('Y-m-d'),
            'dateTo'     => date('Y-m-d'),
            'isSalesman' => true,
            'todayOnly'  => true,
            'todayTotal' => $total,
        ]);
    }

    public function variants(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('sales', 'create');

        $productId = (int) $this->input('product_id', 0);
        $product = $this->productsModel->find($productId);

        if (!$product || $product['status'] !== 'active') {
            $this->json(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $variants = $this->model->getProductVariants($productId);
        $stockQty = $this->productsModel->getProductStock($productId);

        $this->json([
            'success'  => true,
            'product'  => [
                'id'             => (int) $product['id'],
                'name'           => $product['name'],
                'product_code'   => $product['product_code'],
                'sale_price'     => (float) $product['sale_price'],
                'min_sale_price' => (float) $product['min_sale_price'],
                'qty_in_stock'   => $stockQty,
                'image'          => !empty($product['image']) ? \Core\Helpers::productImageUrl((int) $product['id']) : null,
            ],
            'variants' => array_map(static function ($v) use ($product) {
                $attrs = json_decode($v['attributes'] ?? '{}', true) ?: [];
                $label = [];
                foreach ($attrs as $k => $val) {
                    $label[] = $k . ': ' . $val;
                }

                return [
                    'id'                        => (int) $v['id'],
                    'label'                     => $label ? implode(', ', $label) : 'Variant #' . $v['id'],
                    'attributes'                => $attrs,
                    'additional_price_adjustment'=> (float) $v['additional_price_adjustment'],
                    'unit_price'                => (float) $product['sale_price'] + (float) $v['additional_price_adjustment'],
                    'min_sale_price'            => (float) $product['min_sale_price'],
                    'qty_in_stock'              => (int) $v['qty_in_stock'],
                    'barcode'                   => $v['barcode'],
                ];
            }, $variants),
        ]);
    }
}
