<?php

namespace Modules\Products;

use Core\Model;

class CategoriesModel extends Model
{
    protected string $table = 'categories';

    public function getAll(string $search, int $page, string $sort = 'name', string $dir = 'asc'): array
    {
        $allowedSort = ['name', 'code', 'created_at'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'name';
        }
        $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_deleted = 0) AS product_count
                FROM categories c
                WHERE c.is_deleted = 0 AND {$this->tenantFilter('c')}";
        $params = [];
        $this->bindTenant($params);

        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['c.name', 'c.code'], $search);
            $sql .= $likeSql;
            $params = array_merge($params, $likeParams);
        }

        $sql .= " ORDER BY c.{$sort} {$dir}";

        $countSql = "SELECT COUNT(*) as total FROM categories c WHERE c.is_deleted = 0 AND {$this->tenantFilter('c')}";
        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['c.name', 'c.code'], $search);
            $countSql .= $likeSql;
        }

        return $this->paginate($sql, $params, $page, $countSql);
    }

    public function getAllActive(): array
    {
        $sql = "SELECT id, name, code FROM categories
                WHERE is_deleted = 0 AND {$this->tenantFilter()}
                ORDER BY name ASC";
        $params = [];
        $this->bindTenant($params);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCodeById(int $id): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT code FROM categories WHERE id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = ['id' => $id];
        $this->bindTenant($params);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? ($row['code'] ?: null) : null;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as cnt FROM categories
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
            "INSERT INTO categories (tenant_id, name, code, parent_id, created_at, updated_at)
             VALUES (:tenant_id, :name, :code, :parent_id, NOW(), NOW())"
        );
        $stmt->execute([
            'tenant_id' => $this->tenantId,
            'name'      => $data['name'],
            'code'      => $data['code'] ?: null,
            'parent_id' => $data['parent_id'] ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE categories SET name = :name, code = :code, parent_id = :parent_id, updated_at = NOW()
             WHERE id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = [
            'id'        => $id,
            'name'      => $data['name'],
            'code'      => $data['code'] ?: null,
            'parent_id' => $data['parent_id'] ?: null,
        ];
        $this->bindTenant($params);
        return $stmt->execute($params);
    }
}
