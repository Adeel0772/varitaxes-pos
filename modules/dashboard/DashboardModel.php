<?php

namespace Modules\Dashboard;

use Core\Model;

class DashboardModel extends Model
{
    public function todaySales(): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS sale_count, COALESCE(SUM(total_amount), 0) AS total_amount
             FROM sales
             WHERE tenant_id = :tenant_id AND is_deleted = 0 AND DATE(sale_date) = CURDATE()"
        );
        $stmt->execute(['tenant_id' => $this->tenantId]);
        $row = $stmt->fetch();

        return [
            'sale_count'   => (int) ($row['sale_count'] ?? 0),
            'total_amount' => (float) ($row['total_amount'] ?? 0),
        ];
    }

    public function monthComparison(): array
    {
        $currentStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total_amount), 0) AS total
             FROM sales
             WHERE tenant_id = :tenant_id AND is_deleted = 0
             AND YEAR(sale_date) = YEAR(CURDATE()) AND MONTH(sale_date) = MONTH(CURDATE())"
        );
        $currentStmt->execute(['tenant_id' => $this->tenantId]);
        $currentTotal = (float) $currentStmt->fetch()['total'];

        $prevStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total_amount), 0) AS total
             FROM sales
             WHERE tenant_id = :tenant_id AND is_deleted = 0
             AND YEAR(sale_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
             AND MONTH(sale_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))"
        );
        $prevStmt->execute(['tenant_id' => $this->tenantId]);
        $prevTotal = (float) $prevStmt->fetch()['total'];

        $dailyStmt = $this->db->prepare(
            "SELECT DAY(sale_date) AS day_label, COALESCE(SUM(total_amount), 0) AS total
             FROM sales
             WHERE tenant_id = :tenant_id AND is_deleted = 0
             AND YEAR(sale_date) = YEAR(CURDATE()) AND MONTH(sale_date) = MONTH(CURDATE())
             GROUP BY DAY(sale_date)
             ORDER BY day_label"
        );
        $dailyStmt->execute(['tenant_id' => $this->tenantId]);
        $daily = $dailyStmt->fetchAll();

        $changePercent = 0.0;
        if ($prevTotal > 0) {
            $changePercent = (($currentTotal - $prevTotal) / $prevTotal) * 100;
        } elseif ($currentTotal > 0) {
            $changePercent = 100.0;
        }

        return [
            'current_month' => [
                'label' => date('F Y'),
                'total' => $currentTotal,
            ],
            'previous_month' => [
                'label' => date('F Y', strtotime('first day of last month')),
                'total' => $prevTotal,
            ],
            'change_percent' => round($changePercent, 1),
            'daily'          => $daily,
        ];
    }

    public function lowStockCount(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS cnt
             FROM inventory
             WHERE tenant_id = :tenant_id AND is_deleted = 0
             AND qty_in_stock <= low_stock_threshold"
        );
        $stmt->execute(['tenant_id' => $this->tenantId]);
        return (int) $stmt->fetch()['cnt'];
    }

    public function topProducts(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $stmt = $this->db->prepare(
            "SELECT si.product_name_snapshot AS name, si.product_code,
                    SUM(si.qty) AS qty_sold, SUM(si.final_price) AS revenue
             FROM sale_items si
             INNER JOIN sales s ON s.id = si.sale_id AND s.tenant_id = si.tenant_id
             WHERE si.tenant_id = :tenant_id AND si.is_deleted = 0 AND s.is_deleted = 0
             AND YEAR(s.sale_date) = YEAR(CURDATE()) AND MONTH(s.sale_date) = MONTH(CURDATE())
             GROUP BY si.product_id, si.product_name_snapshot, si.product_code
             ORDER BY qty_sold DESC
             LIMIT {$limit}"
        );
        $stmt->execute(['tenant_id' => $this->tenantId]);
        return $stmt->fetchAll();
    }

    public function recentSales(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->db->prepare(
            "SELECT s.id, s.sale_number, s.sale_date, s.total_amount, s.payment_method,
                    u.name AS salesman_name, c.name AS customer_name
             FROM sales s
             INNER JOIN users u ON u.id = s.salesman_id
             LEFT JOIN customers c ON c.id = s.customer_id
             WHERE s.tenant_id = :tenant_id AND s.is_deleted = 0
             ORDER BY s.sale_date DESC
             LIMIT {$limit}"
        );
        $stmt->execute(['tenant_id' => $this->tenantId]);
        return $stmt->fetchAll();
    }

    public function outstandingUdhaar(): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS customer_count, COALESCE(SUM(latest.balance_after), 0) AS total_amount
             FROM (
                 SELECT cl.customer_id, cl.balance_after
                 FROM customer_ledger cl
                 INNER JOIN (
                     SELECT customer_id, MAX(id) AS max_id
                     FROM customer_ledger
                     WHERE tenant_id = :tenant_id AND is_deleted = 0
                     GROUP BY customer_id
                 ) x ON cl.id = x.max_id
                 WHERE cl.tenant_id = :tenant_id2 AND cl.is_deleted = 0 AND cl.balance_after > 0
             ) latest"
        );
        $stmt->execute(['tenant_id' => $this->tenantId, 'tenant_id2' => $this->tenantId]);
        $row = $stmt->fetch();

        return [
            'customer_count' => (int) ($row['customer_count'] ?? 0),
            'total_amount'   => (float) ($row['total_amount'] ?? 0),
        ];
    }

    public function myTodaySales(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS sale_count, COALESCE(SUM(total_amount), 0) AS total_amount
             FROM sales
             WHERE tenant_id = :tenant_id AND salesman_id = :user_id
             AND is_deleted = 0 AND DATE(sale_date) = CURDATE()"
        );
        $stmt->execute(['tenant_id' => $this->tenantId, 'user_id' => $userId]);
        $row = $stmt->fetch();

        return [
            'sale_count'   => (int) ($row['sale_count'] ?? 0),
            'total_amount' => (float) ($row['total_amount'] ?? 0),
        ];
    }
}
