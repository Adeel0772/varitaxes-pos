<?php

namespace Modules\Products;

use Core\Model;

class AttributesModel extends Model
{
    protected string $table = 'product_attributes';

    public function getAll(string $search, int $page): array
    {
        $sql = "SELECT pa.*,
                       (SELECT COUNT(*) FROM product_attribute_values pav
                        WHERE pav.attribute_id = pa.id AND pav.is_deleted = 0) AS value_count
                FROM product_attributes pa
                WHERE pa.is_deleted = 0 AND {$this->tenantFilter('pa')}";
        $params = [];
        $this->bindTenant($params);

        if ($search !== '') {
            $sql .= " AND pa.attribute_name LIKE :search";
            $params['search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY pa.attribute_name ASC";

        $countSql = "SELECT COUNT(*) as total FROM product_attributes pa
                     WHERE pa.is_deleted = 0 AND {$this->tenantFilter('pa')}";
        $countParams = [];
        $this->bindTenant($countParams);
        if ($search !== '') {
            $countSql .= " AND pa.attribute_name LIKE :search";
            $countParams['search'] = '%' . $search . '%';
        }

        return $this->paginate($sql, $params, $page, $countSql);
    }

    public function findAttribute(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM product_attributes
             WHERE id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = ['id' => $id];
        $this->bindTenant($params);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getValues(int $attributeId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM product_attribute_values
             WHERE attribute_id = :attribute_id AND is_deleted = 0 AND {$this->tenantFilter()}
             ORDER BY value ASC"
        );
        $params = ['attribute_id' => $attributeId];
        $this->bindTenant($params);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAllWithValues(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM product_attributes
             WHERE is_deleted = 0 AND {$this->tenantFilter()}
             ORDER BY attribute_name ASC"
        );
        $params = [];
        $this->bindTenant($params);
        $stmt->execute($params);
        $attributes = $stmt->fetchAll();

        foreach ($attributes as &$attr) {
            $attr['values'] = $this->getValues((int) $attr['id']);
        }

        return $attributes;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as cnt FROM product_attributes
                WHERE attribute_name = :name AND is_deleted = 0 AND {$this->tenantFilter()}";
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

    public function createAttribute(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO product_attributes (tenant_id, attribute_name, created_at, updated_at)
             VALUES (:tenant_id, :attribute_name, NOW(), NOW())"
        );
        $stmt->execute([
            'tenant_id'      => $this->tenantId,
            'attribute_name' => $data['attribute_name'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateAttribute(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE product_attributes SET attribute_name = :attribute_name, updated_at = NOW()
             WHERE id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = [
            'id'             => $id,
            'attribute_name' => $data['attribute_name'],
        ];
        $this->bindTenant($params);
        return $stmt->execute($params);
    }

    public function softDeleteAttribute(int $id): bool
    {
        return $this->softDelete($id);
    }

    public function addValue(int $attributeId, string $value): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO product_attribute_values (tenant_id, attribute_id, value, created_at, updated_at)
             VALUES (:tenant_id, :attribute_id, :value, NOW(), NOW())"
        );
        $stmt->execute([
            'tenant_id'    => $this->tenantId,
            'attribute_id' => $attributeId,
            'value'        => $value,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function valueExists(int $attributeId, string $value, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as cnt FROM product_attribute_values
                WHERE attribute_id = :attribute_id AND value = :value AND is_deleted = 0 AND {$this->tenantFilter()}";
        $params = ['attribute_id' => $attributeId, 'value' => $value];
        $this->bindTenant($params);
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['cnt'] > 0;
    }

    public function softDeleteValue(int $valueId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE product_attribute_values SET is_deleted = 1, updated_at = NOW()
             WHERE id = :id AND {$this->tenantFilter()}"
        );
        $params = ['id' => $valueId];
        $this->bindTenant($params);
        return $stmt->execute($params);
    }

    public function findValue(int $valueId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM product_attribute_values
             WHERE id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = ['id' => $valueId];
        $this->bindTenant($params);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
