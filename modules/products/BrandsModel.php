<?php

namespace Modules\Products;

use Core\Model;

class BrandsModel extends Model
{
    protected string $table = 'brands';

    public function getAll(string $search, int $page, string $sort = 'name', string $dir = 'asc'): array
    {
        $allowedSort = ['name', 'created_at'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'name';
        }
        $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

        $sql = "SELECT b.*,
                       (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id AND p.is_deleted = 0) AS product_count
                FROM brands b
                WHERE b.is_deleted = 0 AND {$this->tenantFilter('b')}";
        $params = [];
        $this->bindTenant($params);

        if ($search !== '') {
            $sql .= " AND b.name LIKE :search";
            $params['search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY b.{$sort} {$dir}";

        $countSql = "SELECT COUNT(*) as total FROM brands b WHERE b.is_deleted = 0 AND {$this->tenantFilter('b')}";
        $countParams = [];
        $this->bindTenant($countParams);
        if ($search !== '') {
            $countSql .= " AND b.name LIKE :search";
            $countParams['search'] = '%' . $search . '%';
        }

        return $this->paginate($sql, $params, $page, $countSql, $countParams);
    }

    public function getAllActive(): array
    {
        $sql = "SELECT id, name FROM brands
                WHERE is_deleted = 0 AND {$this->tenantFilter()}
                ORDER BY name ASC";
        $params = [];
        $this->bindTenant($params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as cnt FROM brands
                WHERE name = :name AND is_deleted = 0 AND {$this->tenantFilter()}";
        $params = ['name' => $name];
        $this->bindTenant($params);
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['cnt'] > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO brands (tenant_id, name, created_at, updated_at)
             VALUES (:tenant_id, :name, NOW(), NOW())"
        );
        $stmt->execute([
            'tenant_id' => $this->tenantId,
            'name'      => $data['name'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE brands SET name = :name, updated_at = NOW()
             WHERE id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = [
            'id'   => $id,
            'name' => $data['name'],
        ];
        $this->bindTenant($params);
        return $stmt->execute($params);
    }
}
