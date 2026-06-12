<?php

namespace Modules\Reports;

use Core\Auth;
use Core\Controller;
use Core\Helpers;

class ActivityLogController extends Controller
{
    private ReportsModel $model;

    public function __construct()
    {
        require_once __DIR__ . '/ReportsModel.php';
        $this->model = new ReportsModel();
    }

    public function index(): void
    {
        Auth::requireLogin();
        Auth::checkShopStatus();
        $this->checkPermission('activity_log', 'read');

        $defaults = Helpers::defaultDateRange();
        $from = Helpers::parseFilterDate($this->input('date_from'), $defaults['from']);
        $to = Helpers::parseFilterDate($this->input('date_to'), $defaults['to']);
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $module = trim((string) $this->input('module', '')) ?: null;
        $userId = (int) $this->input('user_id', 0) ?: null;
        $page = max(1, (int) $this->input('page', 1));

        $data = $this->model->activityLogReport($from, $to, $module, $userId);
        $options = $this->model->getFilterOptions();

        $filters = [
            'date_from'         => $from,
            'date_to'           => $to,
            'date_from_display' => Helpers::formatDate($from),
            'date_to_display'   => Helpers::formatDate($to),
        ];

        $this->view('reports/activity_log', [
            'pageTitle'     => 'Activity Log',
            'data'          => $data,
            'filters'       => $filters,
            'filterOptions' => $options,
            'module'        => $module,
            'userId'        => $userId,
            'page'          => $page,
        ]);
    }
}
