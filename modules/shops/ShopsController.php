<?php

namespace Modules\Shops;

use Core\Auth;
use Core\Controller;

class ShopsController extends Controller
{
    private ShopsModel $model;

    public function __construct()
    {
        $this->model = new ShopsModel();
    }

    public function adminDashboard(): void
    {
        Auth::requireSuperAdmin();

        $stats = $this->model->getStats();
        $recentPending = $this->model->getRecentPending(10);

        $this->view('shops/admin_dashboard', [
            'pageTitle'     => 'Admin Dashboard',
            'stats'         => $stats,
            'recentPending' => $recentPending,
        ]);
    }

    public function index(): void
    {
        Auth::requireSuperAdmin();

        $status = $this->input('status', '');
        $search = trim((string) $this->input('search', ''));
        $page = max(1, (int) $this->input('page', 1));

        $shops = $this->model->getAll($status ?: null, $search, $page);

        $this->view('shops/index', [
            'pageTitle' => 'Manage Shops',
            'shops'     => $shops,
            'status'    => $status,
            'search'    => $search,
        ]);
    }

    public function approve(): void
    {
        Auth::requireSuperAdmin();
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $shop = $this->model->findById($id);

        if (!$shop) {
            Auth::flash('error', 'Shop not found.');
            $this->redirect('admin/shops');
        }

        if ($this->model->updateStatus($id, 'active')) {
            $this->logActivity('approve', 'shops', $id, 'Shop approved: ' . $shop['name']);
            Auth::flash('success', 'Shop "' . $shop['name'] . '" has been approved and activated.');
        } else {
            Auth::flash('error', 'Failed to approve shop.');
        }

        $redirect = $this->input('redirect', 'admin/shops');
        $this->redirect($redirect);
    }

    public function suspend(): void
    {
        Auth::requireSuperAdmin();
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $shop = $this->model->findById($id);

        if (!$shop) {
            Auth::flash('error', 'Shop not found.');
            $this->redirect('admin/shops');
        }

        if ($this->model->updateStatus($id, 'suspended')) {
            $this->logActivity('suspend', 'shops', $id, 'Shop suspended: ' . $shop['name']);
            Auth::flash('success', 'Shop "' . $shop['name'] . '" has been suspended.');
        } else {
            Auth::flash('error', 'Failed to suspend shop.');
        }

        $redirect = $this->input('redirect', 'admin/shops');
        $this->redirect($redirect);
    }

    public function show(): void
    {
        Auth::requireSuperAdmin();

        $id = (int) $this->input('id', 0);
        $shop = $this->model->findById($id);

        if (!$shop) {
            Auth::flash('error', 'Shop not found.');
            $this->redirect('admin/shops');
        }

        $this->view('shops/view', [
            'pageTitle' => 'Shop Details',
            'shop'      => $shop,
        ]);
    }

    public function impersonate(): void
    {
        Auth::requireSuperAdmin();
        $this->requirePost();
        $this->verifyCsrf();

        $id = (int) $this->input('id', 0);
        $shop = $this->model->findById($id);

        if (!$shop) {
            Auth::flash('error', 'Shop not found.');
            $this->redirect('admin/shops');
        }

        if ($shop['status'] !== 'active') {
            Auth::flash('error', 'Only active shops can be impersonated.');
            $this->redirect('admin/shops/view?id=' . $id);
        }

        Auth::impersonate($id);
        $this->logActivity('impersonate', 'shops', $id, 'Impersonating shop: ' . $shop['name']);
        Auth::flash('success', 'Now viewing "' . $shop['name'] . '" in read-only mode.');
        $this->redirect('dashboard');
    }

    public function stopImpersonate(): void
    {
        Auth::requireLogin();

        if (Auth::isImpersonating()) {
            $tenantId = Auth::tenantId();
            Auth::stopImpersonating();
            $this->logActivity('stop_impersonate', 'shops', $tenantId, 'Stopped impersonating shop');
            Auth::flash('success', 'Returned to super admin view.');
        }

        $this->redirect(Auth::isSuperAdmin() ? 'admin/dashboard' : 'dashboard');
    }
}
