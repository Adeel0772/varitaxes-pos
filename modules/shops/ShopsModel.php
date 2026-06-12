<?php

namespace Modules\Shops;

use Core\Model;

class ShopsModel extends Model
{
    protected string $table = 'shops';

    public function getAll(?string $status, string $search, int $page): array
    {
        $sql = "SELECT s.*,
                       (SELECT COUNT(*) FROM users u WHERE u.tenant_id = s.id AND u.is_deleted = 0) AS user_count,
                       (SELECT u.email FROM users u WHERE u.tenant_id = s.id AND u.role = 'owner' AND u.is_deleted = 0 LIMIT 1) AS owner_email
                FROM shops s
                WHERE s.is_deleted = 0";
        $params = [];

        if ($status && in_array($status, ['pending', 'active', 'suspended'], true)) {
            $sql .= " AND s.status = :status";
            $params['status'] = $status;
        }

        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['s.name', 's.owner_name', 's.phone', 's.city', 's.slug'], $search);
            $sql .= $likeSql;
            $params = array_merge($params, $likeParams);
        }

        $sql .= " ORDER BY s.created_at DESC";

        $countSql = "SELECT COUNT(*) as total FROM shops s WHERE s.is_deleted = 0";
        $countParams = [];

        if ($status && in_array($status, ['pending', 'active', 'suspended'], true)) {
            $countSql .= " AND s.status = :status";
            $countParams['status'] = $status;
        }

        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['s.name', 's.owner_name', 's.phone', 's.city', 's.slug'], $search);
            $countSql .= $likeSql;
        }

        return $this->paginate($sql, $params, $page, $countSql);
    }

    public function getStats(): array
    {
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) AS suspended
             FROM shops
             WHERE is_deleted = 0"
        );
        $stmt->execute();
        $row = $stmt->fetch();

        return [
            'total'     => (int) ($row['total'] ?? 0),
            'active'    => (int) ($row['active'] ?? 0),
            'pending'   => (int) ($row['pending'] ?? 0),
            'suspended' => (int) ($row['suspended'] ?? 0),
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*,
                    (SELECT COUNT(*) FROM users u WHERE u.tenant_id = s.id AND u.is_deleted = 0) AS user_count,
                    (SELECT COUNT(*) FROM products p WHERE p.tenant_id = s.id AND p.is_deleted = 0) AS product_count,
                    (SELECT COUNT(*) FROM sales sa WHERE sa.tenant_id = s.id AND sa.is_deleted = 0) AS sale_count
             FROM shops s
             WHERE s.id = :id AND s.is_deleted = 0"
        );
        $stmt->execute(['id' => $id]);
        $shop = $stmt->fetch();
        if (!$shop) {
            return null;
        }

        $ownerStmt = $this->db->prepare(
            "SELECT id, name, email, phone, status, last_login, created_at
             FROM users
             WHERE tenant_id = :tenant_id AND role = 'owner' AND is_deleted = 0
             LIMIT 1"
        );
        $ownerStmt->execute(['tenant_id' => $id]);
        $shop['owner'] = $ownerStmt->fetch() ?: null;

        $usersStmt = $this->db->prepare(
            "SELECT id, name, email, role, status, last_login, created_at
             FROM users
             WHERE tenant_id = :tenant_id AND is_deleted = 0
             ORDER BY FIELD(role, 'owner', 'manager', 'salesman'), name"
        );
        $usersStmt->execute(['tenant_id' => $id]);
        $shop['users'] = $usersStmt->fetchAll();

        return $shop;
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['pending', 'active', 'suspended'], true)) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE shops SET status = :status, updated_at = NOW()
             WHERE id = :id AND is_deleted = 0"
        );
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function getRecentPending(int $limit): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->db->prepare(
            "SELECT s.*,
                    (SELECT u.email FROM users u WHERE u.tenant_id = s.id AND u.role = 'owner' AND u.is_deleted = 0 LIMIT 1) AS owner_email
             FROM shops s
             WHERE s.status = 'pending' AND s.is_deleted = 0
             ORDER BY s.created_at DESC
             LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
