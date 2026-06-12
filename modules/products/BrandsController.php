<?php

namespace Modules\Products;

use Core\Auth;
use Core\Controller;

class BrandsController extends Controller
{
    private BrandsModel $model;

    public function __construct()
    {
        require_once __DIR__ . '/BrandsModel.php';
        $this->model = new BrandsModel();
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('brands', 'read');

        $search = trim((string) $this->input('search', ''));
        $page = max(1, (int) $this->input('page', 1));
        $sort = (string) $this->input('sort', 'name');
        $dir = (string) $this->input('dir', 'asc');

        $brands = $this->model->getAll($search, $page, $sort, $dir);
        $editId = (int) $this->input('edit', 0);
        $editBrand = $editId ? $this->model->find($editId) : null;

        $this->view('products/brands/index', [
            'pageTitle' => 'Brands',
            'brands'    => $brands,
            'search'    => $search,
            'sort'      => $sort,
            'dir'       => $dir,
            'editBrand' => $editBrand,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('brands', 'create');
        $this->redirect('brands');
    }

    public function store(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('brands', 'create');
        $this->requirePost();
        $this->verifyCsrf();

        $data = ['name' => trim($this->input('name', ''))];
        $errors = $this->validate($data);
        if ($errors) {
            Auth::flash('error', implode(' ', $errors));
            $this->redirect('brands');
        }

        $id = $this->model->create($data);
        $this->logActivity('create', 'brands', $id, 'Brand created: ' . $data['name']);
        Auth::flash('success', 'Brand created successfully.');
        $this->redirect('brands');
    }

    public function edit(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('brands', 'update');

        $id = (int) $this->input('id', 0);
        if (!$this->model->find($id)) {
            Auth::flash('error', 'Brand not found.');
            $this->redirect('brands');
        }
        $this->redirect('brands?edit=' . $id);
    }

    public function update(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('brands', 'update');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        if (!$this->model->find($id)) {
            Auth::flash('error', 'Brand not found.');
            $this->redirect('brands');
        }

        $data = ['name' => trim($this->input('name', ''))];
        $errors = $this->validate($data, $id);
        if ($errors) {
            Auth::flash('error', implode(' ', $errors));
            $this->redirect('brands?edit=' . $id);
        }

        $this->model->update($id, $data);
        $this->logActivity('update', 'brands', $id, 'Brand updated: ' . $data['name']);
        Auth::flash('success', 'Brand updated successfully.');
        $this->redirect('brands');
    }

    public function delete(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('brands', 'delete');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $brand = $this->model->find($id);
        if (!$brand) {
            Auth::flash('error', 'Brand not found.');
            $this->redirect('brands');
        }

        $this->model->softDelete($id);
        $this->logActivity('delete', 'brands', $id, 'Brand deleted: ' . $brand['name']);
        Auth::flash('success', 'Brand deleted successfully.');
        $this->redirect('brands');
    }

    public function ajaxStore(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('brands', 'create');
        $this->requirePost();
        $this->verifyCsrf();

        $data = ['name' => trim($this->input('name', ''))];
        $errors = $this->validate($data);
        if ($errors) {
            $this->json(['success' => false, 'message' => implode(' ', $errors)], 422);
        }

        $id = $this->model->create($data);
        $this->logActivity('create', 'brands', $id, 'Brand created (inline): ' . $data['name']);

        $this->json([
            'success' => true,
            'message' => 'Brand added.',
            'brand'   => ['id' => $id, 'name' => $data['name']],
        ]);
    }

    private function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        if ($data['name'] === '') {
            $errors[] = 'Brand name is required.';
        } elseif ($this->model->nameExists($data['name'], $excludeId)) {
            $errors[] = 'Brand name already exists.';
        }
        return $errors;
    }
}
