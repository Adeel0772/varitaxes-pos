<?php

namespace Modules\Inventory;

use Core\Auth;
use Core\Model;

class InventoryModel extends Model
{
    protected string $table = 'inventory';

    public function getAll(string $search, ?string $stockFilter, int $page, string $sort = 'name', string $dir = 'asc'): array
    {
        $allowedSort = ['name', 'product_code', 'qty_in_stock', 'low_stock_threshold'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'name';
        }
        $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

        $sortColumn = match ($sort) {
            'qty_in_stock', 'low_stock_threshold' => "i.{$sort}",
            default => "p.{$sort}",
        };

        $sql = "SELECT p.id AS product_id, p.name, p.product_code, p.barcode, p.status,
                       i.id AS inventory_id, i.variant_id,
                       COALESCE(i.qty_in_stock, 0) AS qty_in_stock,
                       COALESCE(i.low_stock_threshold, 5) AS low_stock_threshold,
                       CASE WHEN COALESCE(i.qty_in_stock, 0) <= COALESCE(i.low_stock_threshold, 5) THEN 1 ELSE 0 END AS is_low_stock
                FROM products p
                LEFT JOIN inventory i ON i.product_id = p.id AND i.tenant_id = p.tenant_id
                    AND i.variant_id IS NULL AND i.is_deleted = 0
                WHERE p.is_deleted = 0 AND p.status = 'active' AND {$this->tenantFilter('p')}";
        $params = [];
        $this->bindTenant($params);

        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['p.name', 'p.product_code', 'p.barcode'], $search);
            $sql .= $likeSql;
            $params = array_merge($params, $likeParams);
        }

        if ($stockFilter === 'low') {
            $sql .= " AND COALESCE(i.qty_in_stock, 0) <= COALESCE(i.low_stock_threshold, 5)";
        } elseif ($stockFilter === 'out') {
            $sql .= " AND COALESCE(i.qty_in_stock, 0) = 0";
        }

        $sql .= " ORDER BY {$sortColumn} {$dir}";

        $countSql = "SELECT COUNT(*) as total FROM products p
                     LEFT JOIN inventory i ON i.product_id = p.id AND i.tenant_id = p.tenant_id
                    AND i.variant_id IS NULL AND i.is_deleted = 0
                     WHERE p.is_deleted = 0 AND p.status = 'active' AND {$this->tenantFilter('p')}";
        $countParams = [];
        $this->bindTenant($countParams);
        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['p.name', 'p.product_code', 'p.barcode'], $search);
            $countSql .= $likeSql;
            $countParams = array_merge($countParams, $likeParams);
        }
        if ($stockFilter === 'low') {
            $countSql .= " AND COALESCE(i.qty_in_stock, 0) <= COALESCE(i.low_stock_threshold, 5)";
        } elseif ($stockFilter === 'out') {
            $countSql .= " AND COALESCE(i.qty_in_stock, 0) = 0";
        }

        return $this->paginate($sql, $params, $page, $countSql, $countParams);
    }

    public function getLowStock(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = $this->db->prepare(
            "SELECT p.id AS product_id, p.name, p.product_code, p.barcode,
                    COALESCE(i.qty_in_stock, 0) AS qty_in_stock,
                    COALESCE(i.low_stock_threshold, 5) AS low_stock_threshold
             FROM products p
             LEFT JOIN inventory i ON i.product_id = p.id AND i.variant_id IS NULL AND i.is_deleted = 0
             WHERE p.is_deleted = 0 AND p.status = 'active'
               AND COALESCE(i.qty_in_stock, 0) <= COALESCE(i.low_stock_threshold, 5)
               AND {$this->tenantFilter('p')}
             ORDER BY (COALESCE(i.qty_in_stock, 0) - COALESCE(i.low_stock_threshold, 5)) ASC, p.name ASC
             LIMIT {$limit}"
        );
        $params = [];
        $this->bindTenant($params);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getHistory(int $productId, int $page = 1): array
    {
        $sql = "SELECT sh.*, u.name AS created_by_name, p.name AS product_name, p.product_code
                FROM stock_history sh
                INNER JOIN products p ON p.id = sh.product_id AND p.is_deleted = 0
                LEFT JOIN users u ON u.id = sh.created_by
                WHERE sh.product_id = :product_id AND sh.is_deleted = 0 AND {$this->tenantFilter('sh')}
                ORDER BY sh.created_at DESC";

        $params = ['product_id' => $productId];
        $this->bindTenant($params);

        $countSql = "SELECT COUNT(*) as total FROM stock_history sh
                     WHERE sh.product_id = :product_id AND sh.is_deleted = 0 AND {$this->tenantFilter('sh')}";
        $countParams = ['product_id' => $productId];
        $this->bindTenant($countParams);

        return $this->paginate($sql, $params, $page, $countSql, $countParams);
    }

    public function findProduct(int $productId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.name, p.product_code,
                    COALESCE(i.qty_in_stock, 0) AS qty_in_stock,
                    COALESCE(i.low_stock_threshold, 5) AS low_stock_threshold
             FROM products p
             LEFT JOIN inventory i ON i.product_id = p.id AND i.variant_id IS NULL AND i.is_deleted = 0
             WHERE p.id = :id AND p.is_deleted = 0 AND {$this->tenantFilter('p')}"
        );
        $params = ['id' => $productId];
        $this->bindTenant($params);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function adjustStock(int $productId, ?int $variantId, int $qty, string $reason, int $userId): bool
    {
        if ($qty === 0) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $qtyBefore = $this->getCurrentStock($productId, $variantId);
            $qtyAfter = $qtyBefore + $qty;

            if ($qtyAfter < 0) {
                throw new \RuntimeException('Adjustment would result in negative stock.');
            }

            $inv = $this->db->prepare(
                "SELECT id FROM inventory
                 WHERE product_id = :product_id
                   AND " . ($variantId ? 'variant_id = :variant_id' : 'variant_id IS NULL') . "
                   AND is_deleted = 0 AND {$this->tenantFilter()}"
            );
            $invParams = ['product_id' => $productId];
            if ($variantId) {
                $invParams['variant_id'] = $variantId;
            }
            $this->bindTenant($invParams);
            $inv->execute($invParams);
            $invRow = $inv->fetch();

            if ($invRow) {
                $upd = $this->db->prepare(
                    "UPDATE inventory SET qty_in_stock = :qty, last_updated = NOW(), updated_at = NOW()
                     WHERE id = :id AND {$this->tenantFilter()}"
                );
                $updParams = ['qty' => $qtyAfter, 'id' => $invRow['id']];
                $this->bindTenant($updParams);
                $upd->execute($updParams);
            } else {
                $ins = $this->db->prepare(
                    "INSERT INTO inventory (tenant_id, product_id, variant_id, qty_in_stock, low_stock_threshold, created_at, updated_at)
                     VALUES (:tenant_id, :product_id, :variant_id, :qty, 5, NOW(), NOW())"
                );
                $ins->execute([
                    'tenant_id'  => $this->tenantId,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'qty'        => $qtyAfter,
                ]);
            }

            $hist = $this->db->prepare(
                "INSERT INTO stock_history (
                    tenant_id, product_id, variant_id, change_qty, qty_before, qty_after,
                    reason, reference_type, reference_id, created_by, created_at, updated_at
                 ) VALUES (
                    :tenant_id, :product_id, :variant_id, :change_qty, :qty_before, :qty_after,
                    :reason, 'adjustment', NULL, :created_by, NOW(), NOW()
                 )"
            );
            $hist->execute([
                'tenant_id'  => $this->tenantId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'change_qty' => $qty,
                'qty_before' => $qtyBefore,
                'qty_after'  => $qtyAfter,
                'reason'     => $reason,
                'created_by' => $userId,
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function getCurrentStock(int $productId, ?int $variantId): int
    {
        $sql = "SELECT qty_in_stock FROM inventory
                WHERE product_id = :product_id
                  AND " . ($variantId ? 'variant_id = :variant_id' : 'variant_id IS NULL') . "
                  AND is_deleted = 0 AND {$this->tenantFilter()}";
        $params = ['product_id' => $productId];
        if ($variantId) {
            $params['variant_id'] = $variantId;
        }
        $this->bindTenant($params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? (int) $row['qty_in_stock'] : 0;
    }
}
