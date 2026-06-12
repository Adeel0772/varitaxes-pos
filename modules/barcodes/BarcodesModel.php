<?php

namespace Modules\Barcodes;

use Core\Model;

class BarcodesModel extends Model
{
    protected string $table = 'products';

    public function getProductsForSelection(string $search = '', ?int $categoryId = null, int $page = 1): array
    {
        $sql = "SELECT p.id, p.product_code, p.name, p.barcode, p.sale_price,
                       c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id AND c.is_deleted = 0
                WHERE p.is_deleted = 0 AND p.status = 'active' AND {$this->tenantFilter('p')}";
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

        $sql .= " ORDER BY p.name ASC";

        $countSql = "SELECT COUNT(*) AS total FROM products p
                     WHERE p.is_deleted = 0 AND p.status = 'active' AND {$this->tenantFilter('p')}";
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

        return $this->paginate($sql, $params, $page, $countSql, $countParams);
    }

    public function getProductsByIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT p.id, p.product_code, p.name, p.barcode, p.sale_price
                FROM products p
                WHERE p.is_deleted = 0 AND p.status = 'active'
                AND p.tenant_id = ? AND p.id IN ({$placeholders})
                ORDER BY p.name";

        $params = array_merge([$this->tenantId], $ids);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findProductBarcode(int $productId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, product_code, name, barcode, sale_price
             FROM products
             WHERE id = :id AND is_deleted = 0 AND status = 'active' AND {$this->tenantFilter()}"
        );
        $params = ['id' => $productId];
        $this->bindTenant($params);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
