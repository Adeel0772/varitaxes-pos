<?php
/**
 * CLI smoke tests — run: php tests/smoke-test.php
 */
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/constants.php';

spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'Modules\\')) {
        return;
    }
    $parts = explode('\\', $class);
    if (count($parts) < 3) {
        return;
    }
    $folderMap = [
        'Auth' => 'auth', 'Dashboard' => 'dashboard', 'Shops' => 'shops',
        'Users' => 'users', 'Settings' => 'settings', 'Products' => 'products',
        'Suppliers' => 'suppliers', 'Purchases' => 'purchases', 'Inventory' => 'inventory',
        'Customers' => 'customers', 'Sales' => 'sales', 'Invoices' => 'invoices',
        'Barcodes' => 'barcodes', 'Reports' => 'reports',
    ];
    $folder = $folderMap[$parts[1]] ?? strtolower($parts[1]);
    $file = dirname(__DIR__) . "/modules/{$folder}/{$parts[2]}.php";
    if (is_file($file)) {
        require_once $file;
    }
});

\Core\Auth::init();
$db = \Core\Database::getInstance();

$shop = $db->query("SELECT id FROM shops WHERE status='active' AND is_deleted=0 LIMIT 1")->fetch();
if (!$shop) {
    die("FAIL: No active shop\n");
}
$tenantId = (int) $shop['id'];
$_SESSION['logged_in'] = true;
$_SESSION['user_type'] = 'user';
$_SESSION['user'] = ['id' => 1, 'tenant_id' => $tenantId, 'role' => 'owner'];

$errors = [];

function assert_test(string $name, bool $ok): void
{
    global $errors;
    echo ($ok ? 'PASS' : 'FAIL') . " — {$name}\n";
    if (!$ok) {
        $errors[] = $name;
    }
}

$products = new Modules\Products\ProductsModel();
$search = $products->searchForPos('Du');
assert_test('POS product search', count($search) >= 0 && is_array($search));

$sales = new Modules\Sales\SalesModel();
$list = $sales->getAll('', null, null, null, 1);
assert_test('Sales list query', isset($list['data']));

$suppliers = new Modules\Suppliers\SuppliersModel();
$sList = $suppliers->getAll('', 1);
assert_test('Suppliers list query', isset($sList['data']));

$shops = new Modules\Shops\ShopsModel();
$shopList = $shops->getAll('', '', 1);
assert_test('Shops list query', isset($shopList['data']));

$barcodes = new Modules\Barcodes\BarcodesModel();
$bList = $barcodes->getProductsForSelection('test', null, 1);
assert_test('Barcodes search query', isset($bList['data']));

assert_test('Helpers productImageUrl', \Core\Helpers::productImageUrl(1) !== null);
assert_test('Helpers shopLogoUrl', str_contains(\Core\Helpers::shopLogoUrl(), 'settings/logo'));

echo "\n" . (count($errors) ? count($errors) . ' test(s) failed.' : 'All smoke tests passed.') . "\n";
exit(count($errors) ? 1 : 0);
