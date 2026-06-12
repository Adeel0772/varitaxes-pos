<?php

namespace Modules\Reports;

use Core\Model;

class ReportsModel extends Model
{
    protected function dateRangeParams(?string $dateFrom, ?string $dateTo): array
    {
        return [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ];
    }

    public function salesReport(string $dateFrom, string $dateTo, ?int $salesmanId = null, ?string $paymentMethod = null): array
    {
        $sql = "SELECT s.id, s.sale_number, s.sale_date, s.subtotal, s.discount_amount,
                       s.discount_type, s.total_amount, s.payment_method, s.invoice_printed,
                       u.name AS salesman_name, c.name AS customer_name
                FROM sales s
                INNER JOIN users u ON u.id = s.salesman_id
                LEFT JOIN customers c ON c.id = s.customer_id
                WHERE s.is_deleted = 0 AND {$this->tenantFilter('s')}
                AND DATE(s.sale_date) BETWEEN :date_from AND :date_to";
        $params = $this->dateRangeParams($dateFrom, $dateTo);
        $this->bindTenant($params);

        if ($salesmanId) {
            $sql .= " AND s.salesman_id = :salesman_id";
            $params['salesman_id'] = $salesmanId;
        }
        if ($paymentMethod) {
            $sql .= " AND s.payment_method = :payment_method";
            $params['payment_method'] = $paymentMethod;
        }

        $sql .= " ORDER BY s.sale_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $summary = [
            'sale_count'      => count($rows),
            'total_amount'    => array_sum(array_column($rows, 'total_amount')),
            'total_discount'  => array_sum(array_column($rows, 'discount_amount')),
        ];

        return ['rows' => $rows, 'summary' => $summary];
    }

    public function dailySummary(string $dateFrom, string $dateTo): array
    {
        $stmt = $this->db->prepare(
            "SELECT DATE(s.sale_date) AS sale_day,
                    COUNT(*) AS sale_count,
                    COALESCE(SUM(s.subtotal), 0) AS subtotal,
                    COALESCE(SUM(s.discount_amount), 0) AS discount_total,
                    COALESCE(SUM(s.total_amount), 0) AS total_amount,
                    SUM(CASE WHEN s.payment_method = 'cash' THEN s.total_amount ELSE 0 END) AS cash_total,
                    SUM(CASE WHEN s.payment_method = 'credit' THEN s.total_amount ELSE 0 END) AS credit_total
             FROM sales s
             WHERE s.is_deleted = 0 AND {$this->tenantFilter('s')}
             AND DATE(s.sale_date) BETWEEN :date_from AND :date_to
             GROUP BY DATE(s.sale_date)
             ORDER BY sale_day DESC"
        );
        $params = $this->dateRangeParams($dateFrom, $dateTo);
        $this->bindTenant($params);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'rows' => $rows,
            'summary' => [
                'sale_count'   => array_sum(array_column($rows, 'sale_count')),
                'total_amount' => array_sum(array_column($rows, 'total_amount')),
            ],
        ];
    }

    public function inventoryReport(?int $categoryId = null): array
    {
        $sql = "SELECT p.id, p.product_code, p.name, p.barcode, p.purchase_price, p.sale_price, p.status,
                       c.name AS category_name, b.name AS brand_name,
                       COALESCE(i.qty_in_stock, 0) AS qty_in_stock,
                       COALESCE(i.low_stock_threshold, 5) AS low_stock_threshold,
                       (COALESCE(i.qty_in_stock, 0) * p.purchase_price) AS stock_value
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id AND c.is_deleted = 0
                LEFT JOIN brands b ON b.id = p.brand_id AND b.is_deleted = 0
                LEFT JOIN inventory i ON i.product_id = p.id AND i.variant_id IS NULL AND i.is_deleted = 0
                WHERE p.is_deleted = 0 AND {$this->tenantFilter('p')}";
        $params = [];
        $this->bindTenant($params);

        if ($categoryId) {
            $sql .= " AND p.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }

        $sql .= " ORDER BY p.name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'rows' => $rows,
            'summary' => [
                'product_count' => count($rows),
                'total_qty'     => array_sum(array_column($rows, 'qty_in_stock')),
                'stock_value'   => array_sum(array_column($rows, 'stock_value')),
            ],
        ];
    }

    public function lowStockReport(): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.product_code, p.name, p.sale_price,
                    c.name AS category_name,
                    i.qty_in_stock, i.low_stock_threshold,
                    (i.low_stock_threshold - i.qty_in_stock) AS shortage
             FROM inventory i
             INNER JOIN products p ON p.id = i.product_id AND p.is_deleted = 0
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE i.is_deleted = 0 AND i.variant_id IS NULL
             AND {$this->tenantFilter('i')}
             AND i.qty_in_stock <= i.low_stock_threshold
             ORDER BY shortage DESC, p.name ASC"
        );
        $params = [];
        $this->bindTenant($params);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return ['rows' => $rows, 'summary' => ['item_count' => count($rows)]];
    }

    public function purchaseReport(string $dateFrom, string $dateTo, ?int $supplierId = null): array
    {
        $sql = "SELECT pu.id, pu.purchase_date, pu.total_amount, pu.notes,
                       s.name AS supplier_name, u.name AS created_by_name,
                       (SELECT COUNT(*) FROM purchase_items pi WHERE pi.purchase_id = pu.id AND pi.is_deleted = 0) AS item_count
                FROM purchases pu
                INNER JOIN suppliers s ON s.id = pu.supplier_id
                LEFT JOIN users u ON u.id = pu.created_by
                WHERE pu.is_deleted = 0 AND {$this->tenantFilter('pu')}
                AND pu.purchase_date BETWEEN :date_from AND :date_to";
        $params = $this->dateRangeParams($dateFrom, $dateTo);
        $this->bindTenant($params);

        if ($supplierId) {
            $sql .= " AND pu.supplier_id = :supplier_id";
            $params['supplier_id'] = $supplierId;
        }

        $sql .= " ORDER BY pu.purchase_date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'rows' => $rows,
            'summary' => [
                'purchase_count' => count($rows),
                'total_amount'   => array_sum(array_column($rows, 'total_amount')),
            ],
        ];
    }

    public function profitLoss(string $dateFrom, string $dateTo): array
    {
        $salesStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(s.total_amount), 0) AS revenue,
                    COALESCE(SUM(s.discount_amount), 0) AS discounts,
                    COUNT(*) AS sale_count
             FROM sales s
             WHERE s.is_deleted = 0 AND {$this->tenantFilter('s')}
             AND DATE(s.sale_date) BETWEEN :date_from AND :date_to"
        );
        $params = $this->dateRangeParams($dateFrom, $dateTo);
        $this->bindTenant($params);
        $salesStmt->execute($params);
        $sales = $salesStmt->fetch();

        $cogsStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(si.qty * p.purchase_price), 0) AS cogs
             FROM sale_items si
             INNER JOIN sales s ON s.id = si.sale_id AND s.is_deleted = 0
             INNER JOIN products p ON p.id = si.product_id
             WHERE si.is_deleted = 0 AND {$this->tenantFilter('si')}
             AND DATE(s.sale_date) BETWEEN :date_from AND :date_to"
        );
        $cogsStmt->execute($params);
        $cogs = (float) $cogsStmt->fetch()['cogs'];

        $purchaseStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(total_amount), 0) AS purchase_total
             FROM purchases
             WHERE is_deleted = 0 AND {$this->tenantFilter()}
             AND purchase_date BETWEEN :date_from AND :date_to"
        );
        $purchaseStmt->execute($params);
        $purchases = (float) $purchaseStmt->fetch()['purchase_total'];

        $revenue = (float) $sales['revenue'];
        $grossProfit = $revenue - $cogs;

        return [
            'rows' => [],
            'summary' => [
                'sale_count'     => (int) $sales['sale_count'],
                'revenue'        => $revenue,
                'discounts'      => (float) $sales['discounts'],
                'cogs'           => $cogs,
                'gross_profit'   => $grossProfit,
                'purchase_total' => $purchases,
                'net_estimate'   => $grossProfit,
            ],
        ];
    }

    public function productWiseSales(string $dateFrom, string $dateTo, ?int $categoryId = null): array
    {
        $sql = "SELECT si.product_id, si.product_code, si.product_name_snapshot AS product_name,
                       SUM(si.qty) AS qty_sold,
                       SUM(si.final_price) AS revenue,
                       AVG(si.unit_price) AS avg_price
                FROM sale_items si
                INNER JOIN sales s ON s.id = si.sale_id AND s.is_deleted = 0
                LEFT JOIN products p ON p.id = si.product_id
                WHERE si.is_deleted = 0 AND {$this->tenantFilter('si')}
                AND DATE(s.sale_date) BETWEEN :date_from AND :date_to";
        $params = $this->dateRangeParams($dateFrom, $dateTo);
        $this->bindTenant($params);

        if ($categoryId) {
            $sql .= " AND p.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }

        $sql .= " GROUP BY si.product_id, si.product_code, si.product_name_snapshot
                  ORDER BY qty_sold DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'rows' => $rows,
            'summary' => [
                'product_count' => count($rows),
                'qty_sold'      => array_sum(array_column($rows, 'qty_sold')),
                'revenue'       => array_sum(array_column($rows, 'revenue')),
            ],
        ];
    }

    public function salesmanPerformance(string $dateFrom, string $dateTo): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id AS salesman_id, u.name AS salesman_name,
                    COUNT(s.id) AS sale_count,
                    COALESCE(SUM(s.total_amount), 0) AS total_sales,
                    COALESCE(SUM(s.discount_amount), 0) AS total_discounts,
                    COALESCE(AVG(s.total_amount), 0) AS avg_sale
             FROM sales s
             INNER JOIN users u ON u.id = s.salesman_id
             WHERE s.is_deleted = 0 AND {$this->tenantFilter('s')}
             AND DATE(s.sale_date) BETWEEN :date_from AND :date_to
             GROUP BY u.id, u.name
             ORDER BY total_sales DESC"
        );
        $params = $this->dateRangeParams($dateFrom, $dateTo);
        $this->bindTenant($params);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'rows' => $rows,
            'summary' => [
                'salesman_count' => count($rows),
                'total_sales'    => array_sum(array_column($rows, 'total_sales')),
            ],
        ];
    }

    public function customerLedgerReport(string $dateFrom, string $dateTo, ?int $customerId = null): array
    {
        $sql = "SELECT cl.id, cl.created_at, cl.transaction_type, cl.amount, cl.balance_after, cl.notes,
                       c.name AS customer_name, c.phone AS customer_phone,
                       s.sale_number, u.name AS created_by_name
                FROM customer_ledger cl
                INNER JOIN customers c ON c.id = cl.customer_id
                LEFT JOIN sales s ON s.id = cl.sale_id
                LEFT JOIN users u ON u.id = cl.created_by
                WHERE cl.is_deleted = 0 AND {$this->tenantFilter('cl')}
                AND DATE(cl.created_at) BETWEEN :date_from AND :date_to";
        $params = $this->dateRangeParams($dateFrom, $dateTo);
        $this->bindTenant($params);

        if ($customerId) {
            $sql .= " AND cl.customer_id = :customer_id";
            $params['customer_id'] = $customerId;
        }

        $sql .= " ORDER BY cl.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $saleTotal = 0.0;
        $paymentTotal = 0.0;
        foreach ($rows as $row) {
            if ($row['transaction_type'] === 'sale') {
                $saleTotal += (float) $row['amount'];
            } elseif ($row['transaction_type'] === 'payment') {
                $paymentTotal += (float) $row['amount'];
            }
        }

        return [
            'rows' => $rows,
            'summary' => [
                'entry_count'    => count($rows),
                'sale_total'     => $saleTotal,
                'payment_total'  => $paymentTotal,
            ],
        ];
    }

    public function discountReport(string $dateFrom, string $dateTo): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.id, s.sale_number, s.sale_date, s.subtotal, s.discount_amount, s.discount_type,
                    s.total_amount, u.name AS salesman_name, c.name AS customer_name,
                    (SELECT COALESCE(SUM(si.discount_per_item * si.qty), 0)
                     FROM sale_items si WHERE si.sale_id = s.id AND si.is_deleted = 0) AS item_discounts
             FROM sales s
             INNER JOIN users u ON u.id = s.salesman_id
             LEFT JOIN customers c ON c.id = s.customer_id
             WHERE s.is_deleted = 0 AND {$this->tenantFilter('s')}
             AND DATE(s.sale_date) BETWEEN :date_from AND :date_to
             AND (s.discount_amount > 0 OR EXISTS (
                 SELECT 1 FROM sale_items si2
                 WHERE si2.sale_id = s.id AND si2.is_deleted = 0 AND si2.discount_per_item > 0
             ))
             ORDER BY s.sale_date DESC"
        );
        $params = $this->dateRangeParams($dateFrom, $dateTo);
        $this->bindTenant($params);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'rows' => $rows,
            'summary' => [
                'sale_count'       => count($rows),
                'total_discounts'  => array_sum(array_column($rows, 'discount_amount')),
            ],
        ];
    }

    public function activityLogReport(string $dateFrom, string $dateTo, ?string $module = null, ?int $userId = null): array
    {
        $sql = "SELECT al.id, al.created_at, al.action, al.module, al.record_id, al.details,
                       al.ip_address, al.user_type,
                       CASE WHEN al.user_type = 'super_admin' THEN sa.name ELSE u.name END AS user_name
                FROM activity_log al
                LEFT JOIN users u ON u.id = al.user_id AND al.user_type = 'user'
                LEFT JOIN super_admins sa ON sa.id = al.user_id AND al.user_type = 'super_admin'
                WHERE al.is_deleted = 0 AND al.tenant_id = :tenant_id";
        $params = ['tenant_id' => $this->tenantId, 'date_from' => $dateFrom, 'date_to' => $dateTo];

        $sql .= " AND DATE(al.created_at) BETWEEN :date_from AND :date_to";

        if ($module) {
            $sql .= " AND al.module = :module";
            $params['module'] = $module;
        }
        if ($userId) {
            $sql .= " AND al.user_id = :user_id";
            $params['user_id'] = $userId;
        }

        $sql .= " ORDER BY al.created_at DESC LIMIT 500";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return ['rows' => $rows, 'summary' => ['entry_count' => count($rows)]];
    }

    public function getFilterOptions(): array
    {
        $tenantId = $this->tenantId;

        $salesmen = $this->db->prepare(
            "SELECT id, name FROM users WHERE tenant_id = :tid AND is_deleted = 0 AND status = 'active' ORDER BY name"
        );
        $salesmen->execute(['tid' => $tenantId]);

        $customers = $this->db->prepare(
            "SELECT id, name FROM customers WHERE tenant_id = :tid AND is_deleted = 0 ORDER BY name LIMIT 200"
        );
        $customers->execute(['tid' => $tenantId]);

        $suppliers = $this->db->prepare(
            "SELECT id, name FROM suppliers WHERE tenant_id = :tid AND is_deleted = 0 ORDER BY name"
        );
        $suppliers->execute(['tid' => $tenantId]);

        $categories = $this->db->prepare(
            "SELECT id, name FROM categories WHERE tenant_id = :tid AND is_deleted = 0 ORDER BY name"
        );
        $categories->execute(['tid' => $tenantId]);

        $users = $this->db->prepare(
            "SELECT id, name FROM users WHERE tenant_id = :tid AND is_deleted = 0 ORDER BY name"
        );
        $users->execute(['tid' => $tenantId]);

        return [
            'salesmen'   => $salesmen->fetchAll(),
            'customers'  => $customers->fetchAll(),
            'suppliers'  => $suppliers->fetchAll(),
            'categories' => $categories->fetchAll(),
            'users'      => $users->fetchAll(),
        ];
    }
}
