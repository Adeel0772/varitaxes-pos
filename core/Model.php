<?php

namespace Core;

use PDO;

abstract class Model
{
    protected PDO $db;
    protected string $table;
    protected ?int $tenantId = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tenantId = Auth::tenantId();
    }

    protected function tenantFilter(string $alias = ''): string
    {
        if ($this->tenantId === null) {
            return '1=1';
        }
        $col = $alias ? "{$alias}.tenant_id" : 'tenant_id';
        return "{$col} = :tenant_id";
    }

    protected function bindTenant(array &$params): void
    {
        if ($this->tenantId !== null) {
            $params['tenant_id'] = $this->tenantId;
        }
    }

    /**
     * Build OR-LIKE clause with unique PDO placeholders (required when emulate prepares is off).
     *
     * @param string[] $columns
     * @return array{0: string, 1: array<string, string>}
     */
    protected function orLikeClause(string $prefix, array $columns, string $value): array
    {
        $term = '%' . $value . '%';
        $parts = [];
        $params = [];
        foreach ($columns as $i => $col) {
            $key = $prefix . '_' . $i;
            $parts[] = "{$col} LIKE :{$key}";
            $params[$key] = $term;
        }

        return [' AND (' . implode(' OR ', $parts) . ')', $params];
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id AND is_deleted = 0";
        $params = ['id' => $id];

        if ($this->tenantId !== null && $this->table !== 'shops') {
            $sql .= " AND tenant_id = :tenant_id";
            $params['tenant_id'] = $this->tenantId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function softDelete(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET is_deleted = 1, updated_at = NOW() WHERE id = :id";
        $params = ['id' => $id];

        if ($this->tenantId !== null && $this->table !== 'shops') {
            $sql .= " AND tenant_id = :tenant_id";
            $params['tenant_id'] = $this->tenantId;
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    protected function paginate(string $sql, array $params, int $page, string $countSql = '', ?array $countParams = null): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * PER_PAGE;

        if (!$countSql) {
            $countSql = preg_replace('/SELECT .+ FROM/i', 'SELECT COUNT(*) as total FROM', $sql, 1);
            $countSql = preg_replace('/ORDER BY .+$/i', '', $countSql);
        }

        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($countParams ?? $params);
        $total = (int) $countStmt->fetch()['total'];

        $sql .= " LIMIT " . PER_PAGE . " OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => PER_PAGE,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / PER_PAGE),
        ];
    }
}
