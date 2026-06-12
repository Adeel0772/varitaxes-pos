<?php

namespace Modules\Customers;

use Core\Auth;
use Core\Controller;
use Core\Helpers;

class CustomersController extends Controller
{
    private CustomersModel $model;

    public function __construct()
    {
        require_once __DIR__ . '/CustomersModel.php';
        $this->model = new CustomersModel();
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('customers', 'read');

        $search = trim((string) $this->input('search', ''));
        $page = max(1, (int) $this->input('page', 1));
        $customers = $this->model->getAll($search, $page);

        $this->view('customers/index', [
            'pageTitle' => 'Customers',
            'customers' => $customers,
            'search'    => $search,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('customers', 'create');

        $this->view('customers/create', [
            'pageTitle' => 'Add Customer',
            'old'       => [],
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('customers', 'create');
        $this->requirePost();
        $this->verifyCsrf();

        $data = $this->collectCustomerData();
        $errors = $this->validateCustomer($data);

        if ($errors) {
            $this->view('customers/create', [
                'pageTitle' => 'Add Customer',
                'old'       => $data,
                'errors'    => $errors,
            ]);
            return;
        }

        $id = $this->model->create($data);
        $this->logActivity('create', 'customers', $id, 'Customer created: ' . $data['name']);
        Auth::flash('success', 'Customer created successfully.');
        $this->redirect('customers/view?id=' . $id);
    }

    public function edit(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('customers', 'update');

        $id = (int) $this->input('id', 0);
        $customer = $this->model->find($id);
        if (!$customer) {
            Auth::flash('error', 'Customer not found.');
            $this->redirect('customers');
        }

        $this->view('customers/edit', [
            'pageTitle' => 'Edit Customer',
            'customer'  => $customer,
        ]);
    }

    public function update(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('customers', 'update');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $customer = $this->model->find($id);
        if (!$customer) {
            Auth::flash('error', 'Customer not found.');
            $this->redirect('customers');
        }

        $data = $this->collectCustomerData();
        $errors = $this->validateCustomer($data, $id);

        if ($errors) {
            $this->view('customers/edit', [
                'pageTitle' => 'Edit Customer',
                'customer'  => array_merge($customer, $data),
                'errors'    => $errors,
            ]);
            return;
        }

        $this->model->update($id, $data);
        $this->logActivity('update', 'customers', $id, 'Customer updated: ' . $data['name']);
        Auth::flash('success', 'Customer updated successfully.');
        $this->redirect('customers/view?id=' . $id);
    }

    public function show(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('customers', 'read');

        $id = (int) $this->input('id', 0);
        $customer = $this->model->find($id);
        if (!$customer) {
            Auth::flash('error', 'Customer not found.');
            $this->redirect('customers');
        }

        $this->view('customers/view', [
            'pageTitle' => 'Customer Details',
            'customer'  => $customer,
            'balance'   => $this->model->getBalance($id),
            'ledger'    => $this->model->getLedger($id, 50),
        ]);
    }

    public function delete(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('customers', 'delete');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $customer = $this->model->find($id);
        if (!$customer) {
            Auth::flash('error', 'Customer not found.');
            $this->redirect('customers');
        }

        $balance = $this->model->getBalance($id);
        if ($balance > 0) {
            Auth::flash('error', 'Cannot delete customer with outstanding balance.');
            $this->redirect('customers/view?id=' . $id);
        }

        $this->model->softDelete($id);
        $this->logActivity('delete', 'customers', $id, 'Customer deleted: ' . $customer['name']);
        Auth::flash('success', 'Customer deleted successfully.');
        $this->redirect('customers');
    }

    public function payment(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('customers', 'update');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('customer_id', 0);
        $amount = (float) $this->input('amount', 0);
        $notes = trim((string) $this->input('notes', ''));

        $customer = $this->model->find($id);
        if (!$customer) {
            Auth::flash('error', 'Customer not found.');
            $this->redirect('customers');
        }

        try {
            $this->model->recordPayment($id, $amount, $notes ?: null);
            $this->logActivity('payment', 'customers', $id, 'Payment recorded: ' . Helpers::formatMoney($amount));
            Auth::flash('success', 'Payment recorded successfully.');
        } catch (\InvalidArgumentException $e) {
            Auth::flash('error', $e->getMessage());
        }

        $this->redirect('customers/view?id=' . $id);
    }

    public function statement(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('customers', 'read');

        $id = (int) $this->input('id', 0);
        $customer = $this->model->find($id);
        if (!$customer) {
            Auth::flash('error', 'Customer not found.');
            $this->redirect('customers');
        }

        $this->view('customers/statement', [
            'pageTitle' => 'Customer Statement',
            'customer'  => $customer,
            'balance'   => $this->model->getBalance($id),
            'ledger'    => $this->model->getLedger($id, 500),
        ], 'print');
    }

    public function search(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('customers', 'read');

        $q = trim((string) $this->input('q', ''));
        $customers = $this->model->searchByPhoneOrName($q);

        $this->json([
            'success'   => true,
            'customers' => array_map(static function ($c) {
                return [
                    'id'           => (int) $c['id'],
                    'name'         => $c['name'],
                    'phone'        => $c['phone'],
                    'balance'      => (float) $c['balance'],
                    'credit_limit' => (float) $c['credit_limit'],
                ];
            }, $customers),
        ]);
    }

    private function collectCustomerData(): array
    {
        return [
            'name'         => trim((string) $this->input('name', '')),
            'phone'        => trim((string) $this->input('phone', '')),
            'address'      => trim((string) $this->input('address', '')),
            'notes'        => trim((string) $this->input('notes', '')),
            'credit_limit' => (float) $this->input('credit_limit', 0),
        ];
    }

    private function validateCustomer(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Customer name is required.';
        }
        if ($data['credit_limit'] < 0) {
            $errors[] = 'Credit limit cannot be negative.';
        }
        if ($data['phone'] !== '' && $this->model->phoneExists($data['phone'], $excludeId)) {
            $errors[] = 'Phone number already exists for another customer.';
        }

        return $errors;
    }
}
