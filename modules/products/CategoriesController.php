<?php

namespace Modules\Products;

use Core\Auth;
use Core\Controller;

class CategoriesController extends Controller
{
    private CategoriesModel $model;

    public function __construct()
    {
        require_once __DIR__ . '/CategoriesModel.php';
        $this->model = new CategoriesModel();
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('categories', 'read');

        $search = trim((string) $this->input('search', ''));
        $page = max(1, (int) $this->input('page', 1));
        $sort = (string) $this->input('sort', 'name');
        $dir = (string) $this->input('dir', 'asc');

        $categories = $this->model->getAll($search, $page, $sort, $dir);
        $editId = (int) $this->input('edit', 0);
        $editCategory = $editId ? $this->model->find($editId) : null;

        $this->view('products/categories/index', [
            'pageTitle'    => 'Categories',
            'categories'   => $categories,
            'search'       => $search,
            'sort'         => $sort,
            'dir'          => $dir,
            'editCategory' => $editCategory,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('categories', 'create');
        $this->redirect('categories');
    }

    public function store(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('categories', 'create');
        $this->requirePost();
        $this->verifyCsrf();

        $data = [
            'name'      => trim($this->input('name', '')),
            'code'      => trim($this->input('code', '')),
            'parent_id' => (int) $this->input('parent_id', 0) ?: null,
        ];

        $errors = $this->validate($data);
        if ($errors) {
            Auth::flash('error', implode(' ', $errors));
            $this->redirect('categories');
        }

        $id = $this->model->create($data);
        $this->logActivity('create', 'categories', $id, 'Category created: ' . $data['name']);
        Auth::flash('success', 'Category created successfully.');
        $this->redirect('categories');
    }

    public function edit(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('categories', 'update');

        $id = (int) $this->input('id', 0);
        if (!$this->model->find($id)) {
            Auth::flash('error', 'Category not found.');
            $this->redirect('categories');
        }
        $this->redirect('categories?edit=' . $id);
    }

    public function update(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('categories', 'update');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $category = $this->model->find($id);
        if (!$category) {
            Auth::flash('error', 'Category not found.');
            $this->redirect('categories');
        }

        $data = [
            'name'      => trim($this->input('name', '')),
            'code'      => trim($this->input('code', '')),
            'parent_id' => (int) $this->input('parent_id', 0) ?: null,
        ];

        $errors = $this->validate($data, $id);
        if ($errors) {
            Auth::flash('error', implode(' ', $errors));
            $this->redirect('categories?edit=' . $id);
        }

        if ($data['parent_id'] === $id) {
            Auth::flash('error', 'Category cannot be its own parent.');
            $this->redirect('categories?edit=' . $id);
        }

        $this->model->update($id, $data);
        $this->logActivity('update', 'categories', $id, 'Category updated: ' . $data['name']);
        Auth::flash('success', 'Category updated successfully.');
        $this->redirect('categories');
    }

    public function delete(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('categories', 'delete');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $category = $this->model->find($id);
        if (!$category) {
            Auth::flash('error', 'Category not found.');
            $this->redirect('categories');
        }

        $this->model->softDelete($id);
        $this->logActivity('delete', 'categories', $id, 'Category deleted: ' . $category['name']);
        Auth::flash('success', 'Category deleted successfully.');
        $this->redirect('categories');
    }

    public function ajaxStore(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('categories', 'create');
        $this->requirePost();
        $this->verifyCsrf();

        $data = [
            'name'      => trim($this->input('name', '')),
            'code'      => trim($this->input('code', '')),
            'parent_id' => null,
        ];

        $errors = $this->validate($data);
        if ($errors) {
            $this->json(['success' => false, 'message' => implode(' ', $errors)], 422);
        }

        $id = $this->model->create($data);
        $this->logActivity('create', 'categories', $id, 'Category created (inline): ' . $data['name']);

        $this->json([
            'success' => true,
            'message' => 'Category added.',
            'category' => [
                'id'   => $id,
                'name' => $data['name'],
                'code' => $data['code'],
            ],
        ]);
    }

    private function validate(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        if ($data['name'] === '') {
            $errors[] = 'Category name is required.';
        } elseif ($this->model->nameExists($data['name'], $excludeId)) {
            $errors[] = 'Category name already exists.';
        }
        if ($data['code'] !== '' && !preg_match('/^[A-Za-z0-9\-]{1,10}$/', $data['code'])) {
            $errors[] = 'Category code must be 1-10 alphanumeric characters.';
        }
        return $errors;
    }
}
