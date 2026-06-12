<?php

namespace Modules\Products;

use Core\Auth;
use Core\Helpers;
use Core\Model;

class ProductsModel extends Model
{
    protected string $table = 'products';

    public function getAll(
        string $search,
        ?int $categoryId,
        ?int $brandId,
        ?string $status,
        int $page,
        string $sort = 'created_at',
        string $dir = 'desc'
    ): array {
        $allowedSort = ['name', 'product_code', 'sale_price', 'purchase_price', 'created_at', 'qty_in_stock', 'status'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'created_at';
        }
        $dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        $sortColumn = $sort === 'qty_in_stock' ? 'qty_in_stock' : "p.{$sort}";

        $sql = "SELECT p.*,
                       c.name AS category_name,
                       b.name AS brand_name,
                       COALESCE(i.qty_in_stock, 0) AS qty_in_stock
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id AND c.is_deleted = 0
                LEFT JOIN brands b ON b.id = p.brand_id AND b.is_deleted = 0
                LEFT JOIN inventory i ON i.product_id = p.id AND i.tenant_id = p.tenant_id
                    AND i.variant_id IS NULL AND i.is_deleted = 0
                WHERE p.is_deleted = 0 AND {$this->tenantFilter('p')}";
        $params = [];
        $this->bindTenant($params);

        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['p.name', 'p.product_code', 'p.barcode'], $search);
            $sql .= $likeSql;
            $params = array_merge($params, $likeParams);
        }
        if ($categoryId) {
            $sql .= " AND p.category_id = :category_id";
            $params['category_id'] = $categoryId;
        }
        if ($brandId) {
            $sql .= " AND p.brand_id = :brand_id";
            $params['brand_id'] = $brandId;
        }
        if ($status && in_array($status, ['active', 'inactive'], true)) {
            $sql .= " AND p.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY {$sortColumn} {$dir}";

        $countSql = "SELECT COUNT(*) as total FROM products p
                     WHERE p.is_deleted = 0 AND {$this->tenantFilter('p')}";
        $countParams = [];
        $this->bindTenant($countParams);
        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['p.name', 'p.product_code', 'p.barcode'], $search);
            $countSql .= $likeSql;
            $countParams = array_merge($countParams, $likeParams);
        }
        if ($categoryId) {
            $countSql .= " AND p.category_id = :category_id";
            $countParams['category_id'] = $categoryId;
        }
        if ($brandId) {
            $countSql .= " AND p.brand_id = :brand_id";
            $countParams['brand_id'] = $brandId;
        }
        if ($status && in_array($status, ['active', 'inactive'], true)) {
            $countSql .= " AND p.status = :status";
            $countParams['status'] = $status;
        }

        return $this->paginate($sql, $params, $page, $countSql);
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*,
                    c.name AS category_name, c.code AS category_code,
                    b.name AS brand_name,
                    COALESCE(i.qty_in_stock, 0) AS qty_in_stock,
                    COALESCE(i.low_stock_threshold, 5) AS low_stock_threshold,
                    u.name AS created_by_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN inventory i ON i.product_id = p.id AND i.variant_id IS NULL AND i.is_deleted = 0
             LEFT JOIN users u ON u.id = p.created_by
             WHERE p.id = :id AND p.is_deleted = 0 AND {$this->tenantFilter('p')}"
        );
        $params = ['id' => $id];
        $this->bindTenant($params);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO products (
                    tenant_id, product_code, name, category_id, brand_id, product_type,
                    size, color, origin, description, image, purchase_price, sale_price,
                    min_sale_price, barcode, status, created_by, created_at, updated_at
                 ) VALUES (
                    :tenant_id, :product_code, :name, :category_id, :brand_id, :product_type,
                    :size, :color, :origin, :description, :image, :purchase_price, :sale_price,
                    :min_sale_price, :barcode, :status, :created_by, NOW(), NOW()
                 )"
            );
            $stmt->execute([
                'tenant_id'       => $this->tenantId,
                'product_code'    => $data['product_code'],
                'name'            => $data['name'],
                'category_id'     => $data['category_id'] ?: null,
                'brand_id'        => $data['brand_id'] ?: null,
                'product_type'    => $data['product_type'] ?: null,
                'size'            => $data['size'] ?: null,
                'color'           => $data['color'] ?: null,
                'origin'          => $data['origin'] ?: null,
                'description'     => $data['description'] ?: null,
                'image'           => $data['image'] ?: null,
                'purchase_price'  => $data['purchase_price'],
                'sale_price'      => $data['sale_price'],
                'min_sale_price'  => $data['min_sale_price'],
                'barcode'         => $data['barcode'] ?: null,
                'status'          => $data['status'] ?? 'active',
                'created_by'      => Auth::userId(),
            ]);

            $productId = (int) $this->db->lastInsertId();

            if (empty($data['barcode'])) {
                $barcode = Helpers::generateBarcode($productId, (int) $this->tenantId);
                $upd = $this->db->prepare(
                    "UPDATE products SET barcode = :barcode WHERE id = :id AND tenant_id = :tenant_id"
                );
                $upd->execute(['barcode' => $barcode, 'id' => $productId, 'tenant_id' => $this->tenantId]);
            }

            $initialStock = (int) ($data['initial_stock'] ?? 0);
            $lowStockThreshold = (int) ($data['low_stock_threshold'] ?? 5);

            $invStmt = $this->db->prepare(
                "INSERT INTO inventory (tenant_id, product_id, variant_id, qty_in_stock, low_stock_threshold, created_at, updated_at)
                 VALUES (:tenant_id, :product_id, NULL, :qty, :threshold, NOW(), NOW())"
            );
            $invStmt->execute([
                'tenant_id'  => $this->tenantId,
                'product_id' => $productId,
                'qty'        => max(0, $initialStock),
                'threshold'  => max(0, $lowStockThreshold),
            ]);

            if ($initialStock > 0) {
                $histStmt = $this->db->prepare(
                    "INSERT INTO stock_history (
                        tenant_id, product_id, variant_id, change_qty, qty_before, qty_after,
                        reason, reference_type, reference_id, created_by, created_at, updated_at
                     ) VALUES (
                        :tenant_id, :product_id, NULL, :change_qty, 0, :qty_after,
                        :reason, 'initial', NULL, :created_by, NOW(), NOW()
                     )"
                );
                $histStmt->execute([
                    'tenant_id'  => $this->tenantId,
                    'product_id' => $productId,
                    'change_qty' => $initialStock,
                    'qty_after'  => $initialStock,
                    'reason'     => 'Initial stock on product creation',
                    'created_by' => Auth::userId(),
                ]);
            }

            $this->db->commit();
            return $productId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE products SET
                product_code = :product_code,
                name = :name,
                category_id = :category_id,
                brand_id = :brand_id,
                product_type = :product_type,
                size = :size,
                color = :color,
                origin = :origin,
                description = :description,
                image = :image,
                purchase_price = :purchase_price,
                sale_price = :sale_price,
                min_sale_price = :min_sale_price,
                barcode = :barcode,
                status = :status,
                updated_at = NOW()
             WHERE id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = [
            'id'              => $id,
            'product_code'    => $data['product_code'],
            'name'            => $data['name'],
            'category_id'     => $data['category_id'] ?: null,
            'brand_id'        => $data['brand_id'] ?: null,
            'product_type'    => $data['product_type'] ?: null,
            'size'            => $data['size'] ?: null,
            'color'           => $data['color'] ?: null,
            'origin'          => $data['origin'] ?: null,
            'description'     => $data['description'] ?: null,
            'image'           => $data['image'] ?: null,
            'purchase_price'  => $data['purchase_price'],
            'sale_price'      => $data['sale_price'],
            'min_sale_price'  => $data['min_sale_price'],
            'barcode'         => $data['barcode'] ?: null,
            'status'          => $data['status'] ?? 'active',
        ];
        $this->bindTenant($params);

        if (!$stmt->execute($params)) {
            return false;
        }

        if (isset($data['low_stock_threshold'])) {
            $invStmt = $this->db->prepare(
                "UPDATE inventory SET low_stock_threshold = :threshold, updated_at = NOW()
                 WHERE product_id = :product_id AND variant_id IS NULL AND is_deleted = 0 AND {$this->tenantFilter()}"
            );
            $invParams = [
                'threshold'  => max(0, (int) $data['low_stock_threshold']),
                'product_id' => $id,
            ];
            $this->bindTenant($invParams);
            $invStmt->execute($invParams);
        }

        return true;
    }

    public function toggleStatus(int $id): ?string
    {
        $product = $this->find($id);
        if (!$product) {
            return null;
        }
        $newStatus = $product['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $this->db->prepare(
            "UPDATE products SET status = :status, updated_at = NOW()
             WHERE id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = ['id' => $id, 'status' => $newStatus];
        $this->bindTenant($params);
        if ($stmt->execute($params)) {
            return $newStatus;
        }
        return null;
    }

    public function searchForPos(string $q, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $sql = "SELECT p.id, p.name, p.product_code, p.barcode, p.sale_price, p.min_sale_price,
                       p.purchase_price, p.image, p.product_type, p.size, p.color,
                       COALESCE(i.qty_in_stock, 0) AS qty_in_stock,
                       (SELECT COUNT(*) FROM product_variants pv
                        WHERE pv.product_id = p.id AND pv.is_deleted = 0 AND pv.tenant_id = p.tenant_id) AS variant_count
                FROM products p
                LEFT JOIN inventory i ON i.product_id = p.id AND i.tenant_id = p.tenant_id
                    AND i.variant_id IS NULL AND i.is_deleted = 0
                WHERE p.is_deleted = 0 AND p.status = 'active' AND {$this->tenantFilter('p')}";

        $params = [];
        $this->bindTenant($params);

        if ($q !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('q', ['p.name', 'p.product_code', 'p.barcode'], $q);
            $sql .= $likeSql;
            $params = array_merge($params, $likeParams);
        }

        $sql .= " ORDER BY p.name ASC LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as cnt FROM products
                WHERE product_code = :code AND is_deleted = 0 AND {$this->tenantFilter()}";
        $params = ['code' => $code];
        $this->bindTenant($params);
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['cnt'] > 0;
    }

    public function barcodeExists(string $barcode, ?int $excludeId = null): bool
    {
        if ($barcode === '') {
            return false;
        }
        $sql = "SELECT COUNT(*) as cnt FROM products
                WHERE barcode = :barcode AND is_deleted = 0 AND {$this->tenantFilter()}";
        $params = ['barcode' => $barcode];
        $this->bindTenant($params);
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['cnt'] > 0;
    }

    public function getStockHistory(int $productId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->db->prepare(
            "SELECT sh.*, u.name AS created_by_name
             FROM stock_history sh
             LEFT JOIN users u ON u.id = sh.created_by
             WHERE sh.product_id = :product_id AND sh.is_deleted = 0 AND {$this->tenantFilter('sh')}
             ORDER BY sh.created_at DESC
             LIMIT {$limit}"
        );
        $params = ['product_id' => $productId];
        $this->bindTenant($params);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
