<?php

namespace Modules\Suppliers;

use Core\Auth;
use Core\Controller;

class SuppliersController extends Controller
{
    private SuppliersModel $model;

    public function __construct()
    {
        require_once __DIR__ . '/SuppliersModel.php';
        $this->model = new SuppliersModel();
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('suppliers', 'read');

        $search = trim((string) $this->input('search', ''));
        $page = max(1, (int) $this->input('page', 1));
        $sort = (string) $this->input('sort', 'name');
        $dir = (string) $this->input('dir', 'asc');

        $suppliers = $this->model->getAll($search, $page, $sort, $dir);

        $this->view('suppliers/index', [
            'pageTitle' => 'Suppliers',
            'suppliers' => $suppliers,
            'search'    => $search,
            'sort'      => $sort,
            'dir'       => $dir,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('suppliers', 'create');

        $this->view('suppliers/create', [
            'pageTitle' => 'Add Supplier',
            'old'       => [],
        ]);
    }

    public function store(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('suppliers', 'create');
        $this->requirePost();
        $this->verifyCsrf();

        $data = $this->collectData();
        $errors = $this->validate($data);

        if ($errors) {
            $this->view('suppliers/create', [
                'pageTitle' => 'Add Supplier',
                'old'       => $data,
                'errors'    => $errors,
            ]);
            return;
        }

        $id = $this->model->create($data);
        $this->logActivity('create', 'suppliers', $id, 'Supplier created: ' . $data['name']);
        Auth::flash('success', 'Supplier created successfully.');
        $this->redirect('suppliers');
    }

    public function edit(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('suppliers', 'update');

        $id = (int) $this->input('id', 0);
        $supplier = $this->model->find($id);
        if (!$supplier) {
            Auth::flash('error', 'Supplier not found.');
            $this->redirect('suppliers');
        }

        $this->view('suppliers/edit', [
            'pageTitle' => 'Edit Supplier',
            'supplier'  => $supplier,
        ]);
    }

    public function update(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('suppliers', 'update');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $supplier = $this->model->find($id);
        if (!$supplier) {
            Auth::flash('error', 'Supplier not found.');
            $this->redirect('suppliers');
        }

        $data = $this->collectData();
        $errors = $this->validate($data, $id);

        if ($errors) {
            $this->view('suppliers/edit', [
                'pageTitle' => 'Edit Supplier',
                'supplier'  => array_merge($supplier, $data),
                'errors'    => $errors,
            ]);
            return;
        }

        $this->model->update($id, $data);
        $this->logActivity('update', 'suppliers', $id, 'Supplier updated: ' . $data['name']);
        Auth::flash('success', 'Supplier updated successfully.');
        $this->redirect('suppliers');
    }

    public function delete(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('suppliers', 'delete');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $supplier = $this->model->find($id);
        if (!$supplier) {
            Auth::flash('error', 'Supplier not found.');
            $this->redirect('suppliers');
        }

        if ($this->model->hasPurchases($id)) {
            Auth::flash('error', 'Cannot delete supplier with existing purchases.');
            $this->redirect('suppliers');
        }

        $this->model->softDelete($id);
        $this->logActivity('delete', 'suppliers', $id, 'Supplier deleted: ' . $supplier['name']);
        Auth::flash('success', 'Supplier deleted successfully.');
        $this->redirect('suppliers');
    }

    public function ajaxStore(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('suppliers', 'create');
        $this->requirePost();
        $this->verifyCsrf();

        $data = $this->collectData();
        $errors = $this->validate($data);
        if ($errors) {
            $this->json(['success' => false, 'message' => implode(' ', $errors)], 422);
        }

        $id = $this->model->create($data);
        $this->logActivity('create', 'suppliers', $id, 'Supplier created (inline): ' . $data['name']);

        $this->json([
            'success'  => true,
            'message'  => 'Supplier added.',
            'supplier' => [
                'id'    => $id,
                'name'  => $data['name'],
                'phone' => $data['phone'],
                'city'  => $data['city'],
            ],
        ]);
    }

    private function collectData(): array
    {
        return [
            'name'    => trim($this->input('name', '')),
            'phone'   => trim($this->input('phone', '')),
            'address' => trim($this->input('address', '')),
            'city'    => trim($this->input('city', '')),
            'notes'   => trim($this->input('notes', '')),
        ];
    }

    private function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        if ($data['name'] === '') {
            $errors[] = 'Supplier name is required.';
        } elseif ($this->model->nameExists($data['name'], $excludeId)) {
            $errors[] = 'Supplier name already exists.';
        }
        return $errors;
    }
}
