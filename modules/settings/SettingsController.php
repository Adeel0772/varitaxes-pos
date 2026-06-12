<?php



namespace Modules\Settings;



use Core\Auth;

use Core\Controller;

use Core\Helpers;



class SettingsController extends Controller

{

    private SettingsModel $model;



    private const PAYMENT_METHODS = ['cash', 'jazzcash', 'easypaisa', 'credit', 'other'];



    public function __construct()

    {

        $this->model = new SettingsModel();

    }



    public function index(): void

    {

        Auth::requireLogin();

        Auth::checkShopStatus();

        $this->requireOwner();

        $this->checkPermission('settings', 'read');



        $settings = $this->mergeDefaults($this->model->getAllSettings());

        $shop = $this->model->getShop();



        if ($shop && empty($settings['invoice_format'])) {

            $settings['invoice_format'] = $shop['invoice_format'] ?? 'a4';

        }



        $this->view('settings/index', [

            'pageTitle'      => 'Shop Settings',

            'settings'       => $settings,

            'shop'           => $shop,

            'paymentMethods' => self::PAYMENT_METHODS,

            'errors'         => [],

        ]);

    }



    public function logo(): void

    {

        Auth::requireLogin();

        Auth::checkShopStatus();



        $shop = $this->model->getShop();

        if (!$shop || empty($shop['logo'])) {

            http_response_code(404);

            exit;

        }



        Helpers::serveUploadFile($shop['logo']);

    }



    public function update(): void

    {

        Auth::requireLogin();

        Auth::checkShopStatus();

        $this->requireOwner();

        $this->checkPermission('settings', 'update');

        $this->requirePost();

        $this->verifyCsrf();



        $data = [

            'invoice_header'          => trim((string) $this->input('invoice_header', '')),

            'invoice_footer'          => trim((string) $this->input('invoice_footer', '')),

            'shop_phone'              => trim((string) $this->input('shop_phone', '')),

            'currency_symbol'         => \Core\Helpers::sanitizeCurrencySymbol(trim((string) $this->input('currency_symbol', 'Rs.'))),

            'low_stock_days'          => (string) max(1, (int) $this->input('low_stock_days', 7)),

            'receipt_copies'          => (string) max(1, min(5, (int) $this->input('receipt_copies', 1))),

            'default_payment_method'  => $this->input('default_payment_method', 'cash'),

            'invoice_format'          => $this->input('invoice_format', 'a4'),

            'show_barcode_on_invoice' => $this->input('show_barcode_on_invoice') ? '1' : '0',

        ];



        $errors = $this->validateSettings($data);



        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {

            $uploadErrors = Helpers::validateImage($_FILES['logo']);

            if ($uploadErrors) {

                $errors = array_merge($errors, $uploadErrors);

            }

        }



        if ($errors) {

            $shop = $this->model->getShop();

            $this->view('settings/index', [

                'pageTitle'      => 'Shop Settings',

                'settings'       => $data,

                'shop'           => $shop,

                'paymentMethods' => self::PAYMENT_METHODS,

                'errors'         => $errors,

            ]);

            return;

        }



        if (!$this->model->updateBatch($data)) {

            Auth::flash('error', 'Failed to save settings.');

            $this->redirect('settings');

        }



        $this->model->updateShopInvoiceFormat($data['invoice_format']);



        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {

            $logoPath = Helpers::uploadImage($_FILES['logo'], 'logos');

            if ($logoPath) {

                $this->model->updateShopLogo($logoPath);

            } else {

                Auth::flash('error', 'Settings saved but logo upload failed.');

                $this->redirect('settings');

            }

        }



        $this->logActivity('update', 'settings', null, 'Shop settings updated');

        Auth::flash('success', 'Settings saved successfully.');

        $this->redirect('settings');

    }



    private function requireOwner(): void

    {

        if (Auth::role() !== 'owner') {

            Auth::flash('error', 'Only the shop owner can access settings.');

            $this->redirect('dashboard');

        }

    }



    private function mergeDefaults(array $settings): array

    {

        $defaults = [

            'invoice_header'          => '',

            'invoice_footer'          => 'Thank you for your business!',

            'shop_phone'              => '',

            'currency_symbol'         => 'Rs.',

            'low_stock_days'          => '7',

            'receipt_copies'          => '1',

            'default_payment_method'  => 'cash',

            'invoice_format'          => 'a4',

            'show_barcode_on_invoice' => '0',

        ];



        return array_merge($defaults, $settings);

    }



    private function validateSettings(array $data): array

    {

        $errors = [];



        if ($data['invoice_header'] === '') {

            $errors[] = 'Invoice header is required.';

        }



        if (!in_array($data['default_payment_method'], self::PAYMENT_METHODS, true)) {

            $errors[] = 'Invalid default payment method.';

        }



        if (!in_array($data['invoice_format'], ['a4', 'carbon'], true)) {

            $errors[] = 'Invalid invoice format.';

        }



        return $errors;

    }

}


