<?php

namespace Modules\Customers;

use Core\Auth;
use Core\Model;

class CustomersModel extends Model
{
    protected string $table = 'customers';

    public function getAll(string $search, int $page): array
    {
        $sql = "SELECT c.*,
                       COALESCE(lb.balance_after, 0) AS balance
                FROM customers c
                LEFT JOIN (
                    SELECT cl.customer_id, cl.balance_after
                    FROM customer_ledger cl
                    INNER JOIN (
                        SELECT customer_id, MAX(id) AS max_id
                        FROM customer_ledger
                        WHERE tenant_id = :tenant_id_lb AND is_deleted = 0
                        GROUP BY customer_id
                    ) x ON cl.id = x.max_id
                    WHERE cl.tenant_id = :tenant_id_lb2 AND cl.is_deleted = 0
                ) lb ON lb.customer_id = c.id
                WHERE c.is_deleted = 0 AND {$this->tenantFilter('c')}";
        $params = [];
        $this->bindTenant($params);
        $params['tenant_id_lb'] = $this->tenantId;
        $params['tenant_id_lb2'] = $this->tenantId;

        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['c.name', 'c.phone'], $search);
            $sql .= $likeSql;
            $params = array_merge($params, $likeParams);
        }

        $sql .= " ORDER BY c.name ASC";

        $countSql = "SELECT COUNT(*) as total FROM customers c
                     WHERE c.is_deleted = 0 AND {$this->tenantFilter('c')}";
        $countParams = [];
        $this->bindTenant($countParams);
        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['c.name', 'c.phone'], $search);
            $countSql .= $likeSql;
            $countParams = array_merge($countParams, $likeParams);
        }

        return $this->paginate($sql, $params, $page, $countSql, $countParams);
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO customers (tenant_id, name, phone, address, notes, credit_limit, created_at, updated_at)
             VALUES (:tenant_id, :name, :phone, :address, :notes, :credit_limit, NOW(), NOW())"
        );
        $stmt->execute([
            'tenant_id'    => $this->tenantId,
            'name'         => $data['name'],
            'phone'        => $data['phone'] ?: null,
            'address'      => $data['address'] ?: null,
            'notes'        => $data['notes'] ?: null,
            'credit_limit' => $data['credit_limit'] ?? 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE customers SET
                name = :name,
                phone = :phone,
                address = :address,
                notes = :notes,
                credit_limit = :credit_limit,
                updated_at = NOW()
             WHERE id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = [
            'id'           => $id,
            'name'         => $data['name'],
            'phone'        => $data['phone'] ?: null,
            'address'      => $data['address'] ?: null,
            'notes'        => $data['notes'] ?: null,
            'credit_limit' => $data['credit_limit'] ?? 0,
        ];
        $this->bindTenant($params);

        return $stmt->execute($params);
    }

    public function getBalance(int $customerId): float
    {
        $stmt = $this->db->prepare(
            "SELECT balance_after
             FROM customer_ledger
             WHERE customer_id = :customer_id AND is_deleted = 0 AND {$this->tenantFilter()}
             ORDER BY id DESC
             LIMIT 1"
        );
        $params = ['customer_id' => $customerId];
        $this->bindTenant($params);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ? (float) $row['balance_after'] : 0.0;
    }

    public function addLedgerEntry(
        int $customerId,
        string $transactionType,
        float $amount,
        ?int $saleId = null,
        ?string $notes = null
    ): int {
        $balance = $this->getBalance($customerId);
        $balanceAfter = $balance;

        if ($transactionType === 'sale') {
            $balanceAfter += $amount;
        } elseif ($transactionType === 'payment') {
            $balanceAfter -= $amount;
        } elseif ($transactionType === 'return') {
            $balanceAfter -= $amount;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO customer_ledger (
                tenant_id, customer_id, sale_id, transaction_type, amount, balance_after,
                notes, created_by, created_at, updated_at
             ) VALUES (
                :tenant_id, :customer_id, :sale_id, :transaction_type, :amount, :balance_after,
                :notes, :created_by, NOW(), NOW()
             )"
        );
        $stmt->execute([
            'tenant_id'        => $this->tenantId,
            'customer_id'      => $customerId,
            'sale_id'          => $saleId,
            'transaction_type' => $transactionType,
            'amount'           => $amount,
            'balance_after'    => $balanceAfter,
            'notes'            => $notes,
            'created_by'       => Auth::userId(),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function recordPayment(int $customerId, float $amount, ?string $notes = null): int
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $balance = $this->getBalance($customerId);
        if ($amount > $balance) {
            throw new \InvalidArgumentException('Payment amount exceeds outstanding balance.');
        }

        return $this->addLedgerEntry($customerId, 'payment', $amount, null, $notes);
    }

    public function getLedger(int $customerId, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $stmt = $this->db->prepare(
            "SELECT cl.*,
                    s.sale_number,
                    u.name AS created_by_name
             FROM customer_ledger cl
             LEFT JOIN sales s ON s.id = cl.sale_id
             LEFT JOIN users u ON u.id = cl.created_by
             WHERE cl.customer_id = :customer_id AND cl.is_deleted = 0 AND {$this->tenantFilter('cl')}
             ORDER BY cl.created_at DESC, cl.id DESC
             LIMIT {$limit}"
        );
        $params = ['customer_id' => $customerId];
        $this->bindTenant($params);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function searchByPhoneOrName(string $q, int $limit = 15): array
    {
        $limit = max(1, min(30, $limit));
        $sql = "SELECT c.id, c.name, c.phone, c.credit_limit,
                       COALESCE(lb.balance_after, 0) AS balance
                FROM customers c
                LEFT JOIN (
                    SELECT cl.customer_id, cl.balance_after
                    FROM customer_ledger cl
                    INNER JOIN (
                        SELECT customer_id, MAX(id) AS max_id
                        FROM customer_ledger
                        WHERE tenant_id = :tenant_id_lb AND is_deleted = 0
                        GROUP BY customer_id
                    ) x ON cl.id = x.max_id
                    WHERE cl.tenant_id = :tenant_id_lb2 AND cl.is_deleted = 0
                ) lb ON lb.customer_id = c.id
                WHERE c.is_deleted = 0 AND {$this->tenantFilter('c')}";

        $params = [];
        $this->bindTenant($params);
        $params['tenant_id_lb'] = $this->tenantId;
        $params['tenant_id_lb2'] = $this->tenantId;

        if ($q !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('q', ['c.name', 'c.phone'], $q);
            $sql .= $likeSql;
            $params = array_merge($params, $likeParams);
        }

        $sql .= " ORDER BY c.name ASC LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function phoneExists(string $phone, ?int $excludeId = null): bool
    {
        if ($phone === '') {
            return false;
        }

        $sql = "SELECT COUNT(*) as cnt FROM customers
                WHERE phone = :phone AND is_deleted = 0 AND {$this->tenantFilter()}";
        $params = ['phone' => $phone];
        $this->bindTenant($params);

        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetch()['cnt'] > 0;
    }
}
