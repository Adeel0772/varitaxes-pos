<?php

namespace Modules\Suppliers;

use Core\Model;

class SuppliersModel extends Model
{
    protected string $table = 'suppliers';

    public function getAll(string $search, int $page, string $sort = 'name', string $dir = 'asc'): array
    {
        $allowedSort = ['name', 'phone', 'city', 'created_at'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'name';
        }
        $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

        $sql = "SELECT s.*,
                       (SELECT COUNT(*) FROM purchases p WHERE p.supplier_id = s.id AND p.is_deleted = 0) AS purchase_count
                FROM suppliers s
                WHERE s.is_deleted = 0 AND {$this->tenantFilter('s')}";
        $params = [];
        $this->bindTenant($params);

        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['s.name', 's.phone', 's.city'], $search);
            $sql .= $likeSql;
            $params = array_merge($params, $likeParams);
        }

        $sql .= " ORDER BY s.{$sort} {$dir}";

        $countSql = "SELECT COUNT(*) as total FROM suppliers s
                     WHERE s.is_deleted = 0 AND {$this->tenantFilter('s')}";
        $countParams = [];
        $this->bindTenant($countParams);
        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['s.name', 's.phone', 's.city'], $search);
            $countSql .= $likeSql;
        }

        return $this->paginate($sql, $params, $page, $countSql);
    }

    public function getAllActive(): array
    {
        $sql = "SELECT id, name, phone, city FROM suppliers
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
        $sql = "SELECT COUNT(*) as cnt FROM suppliers
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
            "INSERT INTO suppliers (tenant_id, name, phone, address, city, notes, created_at, updated_at)
             VALUES (:tenant_id, :name, :phone, :address, :city, :notes, NOW(), NOW())"
        );
        $stmt->execute([
            'tenant_id' => $this->tenantId,
            'name'      => $data['name'],
            'phone'     => $data['phone'] ?: null,
            'address'   => $data['address'] ?: null,
            'city'      => $data['city'] ?: null,
            'notes'     => $data['notes'] ?: null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE suppliers SET
                name = :name,
                phone = :phone,
                address = :address,
                city = :city,
                notes = :notes,
                updated_at = NOW()
             WHERE id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = [
            'id'      => $id,
            'name'    => $data['name'],
            'phone'   => $data['phone'] ?: null,
            'address' => $data['address'] ?: null,
            'city'    => $data['city'] ?: null,
            'notes'   => $data['notes'] ?: null,
        ];
        $this->bindTenant($params);
        return $stmt->execute($params);
    }

    public function hasPurchases(int $id): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM purchases
             WHERE supplier_id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = ['id' => $id];
        $this->bindTenant($params);
        $stmt->execute($params);
        return (int) $stmt->fetch()['cnt'] > 0;
    }
}
