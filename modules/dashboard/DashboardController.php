<?php

namespace Modules\Dashboard;

use Core\Auth;
use Core\Controller;

class DashboardController extends Controller
{
    private DashboardModel $model;

    public function __construct()
    {
        $this->model = new DashboardModel();
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();

        if (Auth::isSuperAdmin() && !Auth::isImpersonating()) {
            $this->redirect('admin/dashboard');
        }

        $role = Auth::role();

        if ($role === 'salesman') {
            $todayStats = $this->model->myTodaySales((int) Auth::userId());

            $this->view('dashboard/salesman', [
                'pageTitle'  => 'My Dashboard',
                'todayStats' => $todayStats,
            ]);
            return;
        }

        $this->view('dashboard/index', [
            'pageTitle'        => 'Dashboard',
            'todaySales'       => $this->model->todaySales(),
            'monthComparison'  => $this->model->monthComparison(),
            'lowStockCount'    => $this->model->lowStockCount(),
            'topProducts'      => $this->model->topProducts(),
            'recentSales'      => $this->model->recentSales(),
            'outstandingUdhaar'=> $this->model->outstandingUdhaar(),
        ]);
    }
}
