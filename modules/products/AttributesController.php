<?php

namespace Modules\Products;

use Core\Auth;
use Core\Controller;

class AttributesController extends Controller
{
    private AttributesModel $model;

    public function __construct()
    {
        require_once __DIR__ . '/AttributesModel.php';
        $this->model = new AttributesModel();
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('attributes', 'read');

        $search = trim((string) $this->input('search', ''));
        $page = max(1, (int) $this->input('page', 1));
        $attributes = $this->model->getAll($search, $page);

        $editId = (int) $this->input('edit', 0);
        $editAttribute = $editId ? $this->model->findAttribute($editId) : null;
        $editValues = $editAttribute ? $this->model->getValues($editId) : [];

        $valuesId = (int) $this->input('values', 0);
        $valuesAttribute = $valuesId ? $this->model->findAttribute($valuesId) : null;
        $attributeValues = $valuesAttribute ? $this->model->getValues($valuesId) : [];

        $this->view('products/attributes/index', [
            'pageTitle'       => 'Product Attributes',
            'attributes'      => $attributes,
            'search'          => $search,
            'editAttribute'   => $editAttribute,
            'editValues'      => $editValues,
            'valuesAttribute' => $valuesAttribute,
            'attributeValues' => $attributeValues,
        ]);
    }

    public function create(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('attributes', 'create');
        $this->redirect('attributes');
    }

    public function store(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('attributes', 'create');
        $this->requirePost();
        $this->verifyCsrf();

        $data = ['attribute_name' => trim($this->input('attribute_name', ''))];
        $errors = $this->validateAttribute($data);
        if ($errors) {
            Auth::flash('error', implode(' ', $errors));
            $this->redirect('attributes');
        }

        $id = $this->model->createAttribute($data);
        $this->logActivity('create', 'attributes', $id, 'Attribute created: ' . $data['attribute_name']);
        Auth::flash('success', 'Attribute created successfully.');
        $this->redirect('attributes');
    }

    public function edit(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('attributes', 'update');

        $id = (int) $this->input('id', 0);
        if (!$this->model->findAttribute($id)) {
            Auth::flash('error', 'Attribute not found.');
            $this->redirect('attributes');
        }
        $this->redirect('attributes?edit=' . $id);
    }

    public function update(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('attributes', 'update');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        if (!$this->model->findAttribute($id)) {
            Auth::flash('error', 'Attribute not found.');
            $this->redirect('attributes');
        }

        $data = ['attribute_name' => trim($this->input('attribute_name', ''))];
        $errors = $this->validateAttribute($data, $id);
        if ($errors) {
            Auth::flash('error', implode(' ', $errors));
            $this->redirect('attributes?edit=' . $id);
        }

        $this->model->updateAttribute($id, $data);
        $this->logActivity('update', 'attributes', $id, 'Attribute updated: ' . $data['attribute_name']);
        Auth::flash('success', 'Attribute updated successfully.');
        $this->redirect('attributes');
    }

    public function delete(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('attributes', 'delete');
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $attribute = $this->model->findAttribute($id);
        if (!$attribute) {
            Auth::flash('error', 'Attribute not found.');
            $this->redirect('attributes');
        }

        $this->model->softDeleteAttribute($id);
        $this->logActivity('delete', 'attributes', $id, 'Attribute deleted: ' . $attribute['attribute_name']);
        Auth::flash('success', 'Attribute deleted successfully.');
        $this->redirect('attributes');
    }

    public function values(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('attributes', 'read');

        $id = (int) $this->input('id', 0);
        if (!$this->model->findAttribute($id)) {
            Auth::flash('error', 'Attribute not found.');
            $this->redirect('attributes');
        }
        $this->redirect('attributes?values=' . $id);
    }

    public function addValue(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('attributes', 'update');
        $this->requirePost();
        $this->verifyCsrf();

        $attributeId = (int) $this->input('attribute_id', 0);
        $attribute = $this->model->findAttribute($attributeId);
        if (!$attribute) {
            if (Auth::isAjax()) {
                $this->json(['success' => false, 'message' => 'Attribute not found.'], 404);
            }
            Auth::flash('error', 'Attribute not found.');
            $this->redirect('attributes');
        }

        $value = trim($this->input('value', ''));
        if ($value === '') {
            if (Auth::isAjax()) {
                $this->json(['success' => false, 'message' => 'Value is required.'], 422);
            }
            Auth::flash('error', 'Value is required.');
            $this->redirect('attributes?values=' . $attributeId);
        }

        if ($this->model->valueExists($attributeId, $value)) {
            if (Auth::isAjax()) {
                $this->json(['success' => false, 'message' => 'Value already exists.'], 422);
            }
            Auth::flash('error', 'Value already exists.');
            $this->redirect('attributes?values=' . $attributeId);
        }

        $valueId = $this->model->addValue($attributeId, $value);
        $this->logActivity('create', 'attributes', $valueId, 'Attribute value added: ' . $value);

        if (Auth::isAjax()) {
            $this->json([
                'success' => true,
                'message' => 'Value added.',
                'value'   => ['id' => $valueId, 'value' => $value],
            ]);
        }

        Auth::flash('success', 'Value added successfully.');
        $this->redirect('attributes?values=' . $attributeId);
    }

    public function deleteValue(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('attributes', 'delete');
        $this->requirePost();
        $this->verifyCsrf();

        $valueId = (int) $this->input('id', 0);
        $value = $this->model->findValue($valueId);
        if (!$value) {
            Auth::flash('error', 'Value not found.');
            $this->redirect('attributes');
        }

        $this->model->softDeleteValue($valueId);
        $this->logActivity('delete', 'attributes', $valueId, 'Attribute value deleted: ' . $value['value']);
        Auth::flash('success', 'Value deleted successfully.');
        $this->redirect('attributes?values=' . (int) $value['attribute_id']);
    }

    private function validateAttribute(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        if ($data['attribute_name'] === '') {
            $errors[] = 'Attribute name is required.';
        } elseif ($this->model->nameExists($data['attribute_name'], $excludeId)) {
            $errors[] = 'Attribute name already exists.';
        }
        return $errors;
    }
}
