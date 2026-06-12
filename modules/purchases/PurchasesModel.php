<?php

namespace Modules\Purchases;

use Core\Auth;
use Core\Model;

class PurchasesModel extends Model
{
    protected string $table = 'purchases';

    public function getAll(
        ?int $supplierId,
        ?string $dateFrom,
        ?string $dateTo,
        string $search,
        int $page,
        string $sort = 'purchase_date',
        string $dir = 'desc'
    ): array {
        $allowedSort = ['purchase_date', 'total_amount', 'created_at'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'purchase_date';
        }
        $dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        $sql = "SELECT p.*,
                       s.name AS supplier_name,
                       u.name AS created_by_name,
                       (SELECT COUNT(*) FROM purchase_items pi WHERE pi.purchase_id = p.id AND pi.is_deleted = 0) AS item_count
                FROM purchases p
                INNER JOIN suppliers s ON s.id = p.supplier_id AND s.is_deleted = 0
                LEFT JOIN users u ON u.id = p.created_by
                WHERE p.is_deleted = 0 AND {$this->tenantFilter('p')}";
        $params = [];
        $this->bindTenant($params);

        if ($supplierId) {
            $sql .= " AND p.supplier_id = :supplier_id";
            $params['supplier_id'] = $supplierId;
        }
        if ($dateFrom) {
            $sql .= " AND p.purchase_date >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND p.purchase_date <= :date_to";
            $params['date_to'] = $dateTo;
        }
        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['s.name', 'p.notes'], $search);
            $sql .= $likeSql;
            $params = array_merge($params, $likeParams);
        }

        $sql .= " ORDER BY p.{$sort} {$dir}, p.id DESC";

        $countSql = "SELECT COUNT(*) as total FROM purchases p
                     INNER JOIN suppliers s ON s.id = p.supplier_id AND s.is_deleted = 0
                     WHERE p.is_deleted = 0 AND {$this->tenantFilter('p')}";
        $countParams = [];
        $this->bindTenant($countParams);
        if ($supplierId) {
            $countSql .= " AND p.supplier_id = :supplier_id";
            $countParams['supplier_id'] = $supplierId;
        }
        if ($dateFrom) {
            $countSql .= " AND p.purchase_date >= :date_from";
            $countParams['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $countSql .= " AND p.purchase_date <= :date_to";
            $countParams['date_to'] = $dateTo;
        }
        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['s.name', 'p.notes'], $search);
            $countSql .= $likeSql;
        }

        return $this->paginate($sql, $params, $page, $countSql);
    }

    public function findWithItems(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*,
                    s.name AS supplier_name, s.phone AS supplier_phone, s.city AS supplier_city,
                    u.name AS created_by_name
             FROM purchases p
             INNER JOIN suppliers s ON s.id = p.supplier_id AND s.is_deleted = 0
             LEFT JOIN users u ON u.id = p.created_by
             WHERE p.id = :id AND p.is_deleted = 0 AND {$this->tenantFilter('p')}"
        );
        $params = ['id' => $id];
        $this->bindTenant($params);
        $stmt->execute($params);
        $purchase = $stmt->fetch();
        if (!$purchase) {
            return null;
        }

        $itemsStmt = $this->db->prepare(
            "SELECT pi.*,
                    pr.name AS product_name, pr.product_code, pr.barcode
             FROM purchase_items pi
             INNER JOIN products pr ON pr.id = pi.product_id AND pr.is_deleted = 0
             WHERE pi.purchase_id = :purchase_id AND pi.is_deleted = 0 AND {$this->tenantFilter('pi')}
             ORDER BY pi.id ASC"
        );
        $itemParams = ['purchase_id' => $id];
        $this->bindTenant($itemParams);
        $itemsStmt->execute($itemParams);
        $purchase['items'] = $itemsStmt->fetchAll();

        return $purchase;
    }

    public function canEdit(array $purchase): bool
    {
        return ($purchase['purchase_date'] ?? '') === date('Y-m-d');
    }

    public function create(array $data, array $items): int
    {
        $this->db->beginTransaction();
        try {
            $totalAmount = 0.0;
            foreach ($items as $item) {
                $totalAmount += (float) $item['purchase_price'] * (int) $item['qty'];
            }

            $stmt = $this->db->prepare(
                "INSERT INTO purchases (tenant_id, supplier_id, purchase_date, total_amount, notes, created_by, created_at, updated_at)
                 VALUES (:tenant_id, :supplier_id, :purchase_date, :total_amount, :notes, :created_by, NOW(), NOW())"
            );
            $stmt->execute([
                'tenant_id'     => $this->tenantId,
                'supplier_id'   => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'total_amount'  => $totalAmount,
                'notes'         => $data['notes'] ?: null,
                'created_by'    => Auth::userId(),
            ]);

            $purchaseId = (int) $this->db->lastInsertId();
            $this->processItems($purchaseId, $items, false);

            $this->db->commit();
            return $purchaseId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updatePurchase(int $id, array $data, array $items): bool
    {
        $purchase = $this->findWithItems($id);
        if (!$purchase || !$this->canEdit($purchase)) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            foreach ($purchase['items'] as $oldItem) {
                $this->applyStockChange(
                    (int) $oldItem['product_id'],
                    $oldItem['variant_id'] ? (int) $oldItem['variant_id'] : null,
                    -(int) $oldItem['qty'],
                    'Purchase update — stock reversed',
                    'adjustment',
                    $id
                );
            }

            $softDel = $this->db->prepare(
                "UPDATE purchase_items SET is_deleted = 1, updated_at = NOW()
                 WHERE purchase_id = :purchase_id AND is_deleted = 0 AND {$this->tenantFilter()}"
            );
            $delParams = ['purchase_id' => $id];
            $this->bindTenant($delParams);
            $softDel->execute($delParams);

            $totalAmount = 0.0;
            foreach ($items as $item) {
                $totalAmount += (float) $item['purchase_price'] * (int) $item['qty'];
            }

            $upd = $this->db->prepare(
                "UPDATE purchases SET
                    supplier_id = :supplier_id,
                    purchase_date = :purchase_date,
                    total_amount = :total_amount,
                    notes = :notes,
                    updated_at = NOW()
                 WHERE id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
            );
            $updParams = [
                'id'            => $id,
                'supplier_id'   => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'total_amount'  => $totalAmount,
                'notes'         => $data['notes'] ?: null,
            ];
            $this->bindTenant($updParams);
            $upd->execute($updParams);

            $this->processItems($id, $items, true);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function processItems(int $purchaseId, array $items, bool $isUpdate): void
    {
        $itemStmt = $this->db->prepare(
            "INSERT INTO purchase_items (tenant_id, purchase_id, product_id, variant_id, qty, purchase_price, created_at, updated_at)
             VALUES (:tenant_id, :purchase_id, :product_id, :variant_id, :qty, :purchase_price, NOW(), NOW())"
        );

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $variantId = !empty($item['variant_id']) ? (int) $item['variant_id'] : null;
            $qty = (int) $item['qty'];
            $price = (float) $item['purchase_price'];

            $itemStmt->execute([
                'tenant_id'      => $this->tenantId,
                'purchase_id'    => $purchaseId,
                'product_id'     => $productId,
                'variant_id'     => $variantId,
                'qty'            => $qty,
                'purchase_price' => $price,
            ]);

            $this->applyStockChange(
                $productId,
                $variantId,
                $qty,
                $isUpdate ? 'Purchase updated — stock added' : 'Stock received via purchase',
                'purchase',
                $purchaseId
            );

            $priceUpd = $this->db->prepare(
                "UPDATE products SET purchase_price = :price, updated_at = NOW()
                 WHERE id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
            );
            $priceParams = ['price' => $price, 'id' => $productId];
            $this->bindTenant($priceParams);
            $priceUpd->execute($priceParams);
        }
    }

    private function applyStockChange(
        int $productId,
        ?int $variantId,
        int $changeQty,
        string $reason,
        string $referenceType,
        int $referenceId
    ): void {
        $qtyBefore = $this->getCurrentStock($productId, $variantId);
        $qtyAfter = $qtyBefore + $changeQty;

        if ($qtyAfter < 0) {
            throw new \RuntimeException('Insufficient stock for purchase update.');
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
                :reason, :reference_type, :reference_id, :created_by, NOW(), NOW()
             )"
        );
        $hist->execute([
            'tenant_id'      => $this->tenantId,
            'product_id'     => $productId,
            'variant_id'     => $variantId,
            'change_qty'     => $changeQty,
            'qty_before'     => $qtyBefore,
            'qty_after'      => $qtyAfter,
            'reason'         => $reason,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'created_by'     => Auth::userId(),
        ]);
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
