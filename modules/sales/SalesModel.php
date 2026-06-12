<?php

namespace Modules\Sales;

use Core\Auth;
use Core\Helpers;
use Core\Model;

class SalesModel extends Model
{
    protected string $table = 'sales';

    public function getAll(
        string $search,
        ?string $dateFrom,
        ?string $dateTo,
        ?int $salesmanId,
        int $page
    ): array {
        $sql = "SELECT s.*,
                       u.name AS salesman_name,
                       c.name AS customer_name
                FROM sales s
                INNER JOIN users u ON u.id = s.salesman_id
                LEFT JOIN customers c ON c.id = s.customer_id
                WHERE s.is_deleted = 0 AND {$this->tenantFilter('s')}";
        $params = [];
        $this->bindTenant($params);

        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['s.sale_number', 'c.name', 'c.phone'], $search);
            $sql .= $likeSql;
            $params = array_merge($params, $likeParams);
        }
        if ($dateFrom) {
            $sql .= " AND DATE(s.sale_date) >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND DATE(s.sale_date) <= :date_to";
            $params['date_to'] = $dateTo;
        }
        if ($salesmanId) {
            $sql .= " AND s.salesman_id = :salesman_id";
            $params['salesman_id'] = $salesmanId;
        }

        $sql .= " ORDER BY s.sale_date DESC";

        $countSql = "SELECT COUNT(*) as total
                     FROM sales s
                     LEFT JOIN customers c ON c.id = s.customer_id
                     WHERE s.is_deleted = 0 AND {$this->tenantFilter('s')}";
        $countParams = [];
        $this->bindTenant($countParams);
        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['s.sale_number', 'c.name', 'c.phone'], $search);
            $countSql .= $likeSql;
            $countParams = array_merge($countParams, $likeParams);
        }
        if ($dateFrom) {
            $countSql .= " AND DATE(s.sale_date) >= :date_from";
            $countParams['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $countSql .= " AND DATE(s.sale_date) <= :date_to";
            $countParams['date_to'] = $dateTo;
        }
        if ($salesmanId) {
            $countSql .= " AND s.salesman_id = :salesman_id";
            $countParams['salesman_id'] = $salesmanId;
        }

        return $this->paginate($sql, $params, $page, $countSql, $countParams);
    }

    public function findWithItems(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*,
                    u.name AS salesman_name,
                    c.name AS customer_name,
                    c.phone AS customer_phone,
                    sh.name AS shop_name,
                    sh.phone AS shop_phone,
                    sh.address AS shop_address
             FROM sales s
             INNER JOIN users u ON u.id = s.salesman_id
             LEFT JOIN customers c ON c.id = s.customer_id
             INNER JOIN shops sh ON sh.id = s.tenant_id
             WHERE s.id = :id AND s.is_deleted = 0 AND {$this->tenantFilter('s')}"
        );
        $params = ['id' => $id];
        $this->bindTenant($params);
        $stmt->execute($params);
        $sale = $stmt->fetch();

        if (!$sale) {
            return null;
        }

        $itemsStmt = $this->db->prepare(
            "SELECT si.*
             FROM sale_items si
             WHERE si.sale_id = :sale_id AND si.is_deleted = 0 AND {$this->tenantFilter('si')}
             ORDER BY si.id ASC"
        );
        $itemParams = ['sale_id' => $id];
        $this->bindTenant($itemParams);
        $itemsStmt->execute($itemParams);
        $sale['items'] = $itemsStmt->fetchAll();

        return $sale;
    }

    public function getMyTodaySales(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, c.name AS customer_name
             FROM sales s
             LEFT JOIN customers c ON c.id = s.customer_id
             WHERE s.is_deleted = 0 AND {$this->tenantFilter('s')}
             AND s.salesman_id = :user_id AND DATE(s.sale_date) = CURDATE()
             ORDER BY s.sale_date DESC"
        );
        $params = ['user_id' => $userId];
        $this->bindTenant($params);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getProductVariants(int $productId): array
    {
        $stmt = $this->db->prepare(
            "SELECT pv.*,
                    COALESCE(i.qty_in_stock, pv.stock_qty, 0) AS qty_in_stock
             FROM product_variants pv
             LEFT JOIN inventory i ON i.product_id = pv.product_id
                 AND i.variant_id = pv.id AND i.is_deleted = 0 AND i.tenant_id = pv.tenant_id
             WHERE pv.product_id = :product_id AND pv.is_deleted = 0 AND {$this->tenantFilter('pv')}
             ORDER BY pv.id ASC"
        );
        $params = ['product_id' => $productId];
        $this->bindTenant($params);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function completeSale(array $data): array
    {
        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new \InvalidArgumentException('Cart is empty.');
        }

        $paymentMethod = $data['payment_method'] ?? 'cash';
        $allowedMethods = ['cash', 'jazzcash', 'easypaisa', 'credit', 'other'];
        if (!in_array($paymentMethod, $allowedMethods, true)) {
            throw new \InvalidArgumentException('Invalid payment method.');
        }

        $customerId = !empty($data['customer_id']) ? (int) $data['customer_id'] : null;
        if ($paymentMethod === 'credit') {
            if (!$customerId) {
                throw new \InvalidArgumentException('Customer is required for credit sales.');
            }
        }

        $shopSlug = $this->getShopSlug();
        $saleNumber = Helpers::generateSaleNumber((int) $this->tenantId, $this->db, $shopSlug);

        $validatedItems = [];
        $subtotal = 0.0;

        foreach ($items as $idx => $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $variantId = !empty($item['variant_id']) ? (int) $item['variant_id'] : null;
            $qty = (int) ($item['qty'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $discountPerItem = (float) ($item['discount_per_item'] ?? 0);
            $discountType = ($item['discount_type'] ?? 'flat') === 'percent' ? 'percent' : 'flat';

            if ($productId <= 0 || $qty <= 0 || $unitPrice <= 0) {
                throw new \InvalidArgumentException('Invalid item at position ' . ($idx + 1) . '.');
            }

            $product = $this->getActiveProduct($productId);
            if (!$product) {
                throw new \InvalidArgumentException('Product not found or inactive.');
            }

            $variant = null;
            if ($variantId) {
                $variant = $this->getActiveVariant($productId, $variantId);
                if (!$variant) {
                    throw new \InvalidArgumentException('Invalid variant for ' . $product['name'] . '.');
                }
            }

            $minPrice = (float) $product['min_sale_price'];
            if ($minPrice > 0 && $unitPrice < $minPrice - 0.001) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Unit price for "%s" cannot be below minimum sale price (%s).',
                        $product['name'],
                        Helpers::formatMoney($minPrice)
                    )
                );
            }

            $lineSubtotal = $unitPrice * $qty;
            if ($discountType === 'percent') {
                if ($discountPerItem > 100) {
                    throw new \InvalidArgumentException('Item discount cannot exceed 100%.');
                }
                $lineDiscount = $lineSubtotal * ($discountPerItem / 100);
            } else {
                $lineDiscount = $discountPerItem * $qty;
            }
            $lineDiscount = min($lineDiscount, $lineSubtotal);
            $finalPrice = round($lineSubtotal - $lineDiscount, 2);

            $effectiveUnit = $finalPrice / $qty;
            if ($minPrice > 0 && $effectiveUnit < $minPrice - 0.001) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Price for "%s" cannot be below minimum sale price (%s).',
                        $product['name'],
                        Helpers::formatMoney($minPrice)
                    )
                );
            }

            $stockQty = $this->getStockQty($productId, $variantId);
            if ($stockQty < $qty) {
                throw new \InvalidArgumentException(
                    sprintf('Insufficient stock for "%s". Available: %d', $product['name'], $stockQty)
                );
            }

            $productName = $product['name'];
            if ($variant && !empty($variant['attributes'])) {
                $attrs = json_decode($variant['attributes'], true);
                if (is_array($attrs) && $attrs) {
                    $parts = [];
                    foreach ($attrs as $k => $v) {
                        $parts[] = $k . ': ' . $v;
                    }
                    $productName .= ' (' . implode(', ', $parts) . ')';
                }
            }

            $validatedItems[] = [
                'product_id'          => $productId,
                'variant_id'          => $variantId,
                'product_code'        => $product['product_code'],
                'product_name_snapshot'=> $productName,
                'qty'                 => $qty,
                'unit_price'          => $unitPrice,
                'discount_per_item'   => $discountPerItem,
                'discount_type'       => $discountType,
                'final_price'         => $finalPrice,
                'purchase_price'      => (float) $product['purchase_price'],
            ];

            $subtotal += $finalPrice;
        }

        $saleDiscountAmount = (float) ($data['discount_amount'] ?? 0);
        $saleDiscountType = ($data['discount_type'] ?? 'flat') === 'percent' ? 'percent' : 'flat';

        if ($saleDiscountType === 'percent') {
            if ($saleDiscountAmount > 100) {
                throw new \InvalidArgumentException('Sale discount cannot exceed 100%.');
            }
            $saleDiscount = round($subtotal * ($saleDiscountAmount / 100), 2);
        } else {
            if ($saleDiscountAmount > $subtotal) {
                throw new \InvalidArgumentException('Sale discount cannot exceed subtotal.');
            }
            $saleDiscount = $saleDiscountAmount;
        }
        $saleDiscount = min(max(0, $saleDiscount), $subtotal);

        $totalAmount = round($subtotal - $saleDiscount, 2);
        if ($totalAmount <= 0) {
            throw new \InvalidArgumentException('Sale total must be greater than zero.');
        }

        if ($paymentMethod === 'credit' && $customerId) {
            $this->validateCreditLimit($customerId, $totalAmount);
        }

        $amountTendered = isset($data['amount_tendered']) ? (float) $data['amount_tendered'] : null;
        $changeAmount = null;
        if ($paymentMethod === 'cash') {
            if ($amountTendered === null || $amountTendered <= 0) {
                throw new \InvalidArgumentException('Amount tendered is required for cash payment.');
            }
            if ($amountTendered < $totalAmount - 0.001) {
                throw new \InvalidArgumentException('Amount tendered is less than sale total.');
            }
            $changeAmount = max(0, round($amountTendered - $totalAmount, 2));
        }

        $this->db->beginTransaction();
        try {
            $saleStmt = $this->db->prepare(
                "INSERT INTO sales (
                    tenant_id, sale_number, customer_id, salesman_id, sale_date,
                    subtotal, discount_amount, discount_type, total_amount,
                    payment_method, amount_tendered, change_amount, notes,
                    created_at, updated_at
                 ) VALUES (
                    :tenant_id, :sale_number, :customer_id, :salesman_id, NOW(),
                    :subtotal, :discount_amount, :discount_type, :total_amount,
                    :payment_method, :amount_tendered, :change_amount, :notes,
                    NOW(), NOW()
                 )"
            );
            $saleStmt->execute([
                'tenant_id'        => $this->tenantId,
                'sale_number'      => $saleNumber,
                'customer_id'      => $customerId,
                'salesman_id'      => Auth::userId(),
                'subtotal'         => $subtotal,
                'discount_amount'  => $saleDiscount,
                'discount_type'    => $saleDiscountType,
                'total_amount'     => $totalAmount,
                'payment_method'   => $paymentMethod,
                'amount_tendered'  => $amountTendered,
                'change_amount'    => $changeAmount,
                'notes'            => !empty($data['notes']) ? trim($data['notes']) : null,
            ]);

            $saleId = (int) $this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                "INSERT INTO sale_items (
                    tenant_id, sale_id, product_id, variant_id, product_code, product_name_snapshot,
                    qty, unit_price, discount_per_item, discount_type, final_price,
                    created_at, updated_at
                 ) VALUES (
                    :tenant_id, :sale_id, :product_id, :variant_id, :product_code, :product_name_snapshot,
                    :qty, :unit_price, :discount_per_item, :discount_type, :final_price,
                    NOW(), NOW()
                 )"
            );

            foreach ($validatedItems as $vi) {
                $itemStmt->execute([
                    'tenant_id'            => $this->tenantId,
                    'sale_id'              => $saleId,
                    'product_id'           => $vi['product_id'],
                    'variant_id'           => $vi['variant_id'],
                    'product_code'         => $vi['product_code'],
                    'product_name_snapshot'=> $vi['product_name_snapshot'],
                    'qty'                  => $vi['qty'],
                    'unit_price'           => $vi['unit_price'],
                    'discount_per_item'    => $vi['discount_per_item'],
                    'discount_type'        => $vi['discount_type'],
                    'final_price'          => $vi['final_price'],
                ]);

                $this->deductStock(
                    $vi['product_id'],
                    $vi['variant_id'],
                    $vi['qty'],
                    $saleId,
                    'Sale #' . $saleNumber
                );
            }

            if ($paymentMethod === 'credit' && $customerId) {
                $this->addCreditLedgerEntry($customerId, $saleId, $totalAmount, $saleNumber);
            }

            $this->db->commit();

            return [
                'id'           => $saleId,
                'sale_number'  => $saleNumber,
                'total_amount' => $totalAmount,
                'change_amount'=> $changeAmount,
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function getActiveProduct(int $productId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM products
             WHERE id = :id AND status = 'active' AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = ['id' => $productId];
        $this->bindTenant($params);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function getActiveVariant(int $productId, int $variantId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM product_variants
             WHERE id = :id AND product_id = :product_id AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = ['id' => $variantId, 'product_id' => $productId];
        $this->bindTenant($params);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    private function getStockQty(int $productId, ?int $variantId): int
    {
        if ($variantId) {
            $stmt = $this->db->prepare(
                "SELECT COALESCE(i.qty_in_stock, pv.stock_qty, 0) AS qty
                 FROM product_variants pv
                 LEFT JOIN inventory i ON i.product_id = pv.product_id
                     AND i.variant_id = pv.id AND i.is_deleted = 0 AND i.tenant_id = pv.tenant_id
                 WHERE pv.id = :variant_id AND pv.product_id = :product_id
                 AND pv.is_deleted = 0 AND {$this->tenantFilter('pv')}"
            );
            $params = ['variant_id' => $variantId, 'product_id' => $productId];
            $this->bindTenant($params);
            $stmt->execute($params);
            $row = $stmt->fetch();

            return (int) ($row['qty'] ?? 0);
        }

        $stmt = $this->db->prepare(
            "SELECT COALESCE(qty_in_stock, 0) AS qty FROM inventory
             WHERE product_id = :product_id AND variant_id IS NULL AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = ['product_id' => $productId];
        $this->bindTenant($params);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return (int) ($row['qty'] ?? 0);
    }

    private function deductStock(int $productId, ?int $variantId, int $qty, int $saleId, string $reason): void
    {
        $beforeQty = $this->getStockQty($productId, $variantId);

        if ($variantId) {
            $stmt = $this->db->prepare(
                "SELECT id, qty_in_stock FROM inventory
                 WHERE product_id = :product_id AND variant_id = :variant_id
                 AND is_deleted = 0 AND {$this->tenantFilter()}"
            );
            $params = ['product_id' => $productId, 'variant_id' => $variantId];
            $this->bindTenant($params);
            $stmt->execute($params);
            $inv = $stmt->fetch();

            $afterQty = $beforeQty - $qty;

            if ($inv) {
                $upd = $this->db->prepare(
                    "UPDATE inventory SET qty_in_stock = :qty, last_updated = NOW(), updated_at = NOW()
                     WHERE id = :id AND {$this->tenantFilter()}"
                );
                $updParams = ['qty' => $afterQty, 'id' => $inv['id']];
                $this->bindTenant($updParams);
                $upd->execute($updParams);
            } else {
                $ins = $this->db->prepare(
                    "INSERT INTO inventory (tenant_id, product_id, variant_id, qty_in_stock, created_at, updated_at)
                     VALUES (:tenant_id, :product_id, :variant_id, :qty, NOW(), NOW())"
                );
                $ins->execute([
                    'tenant_id'  => $this->tenantId,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'qty'        => $afterQty,
                ]);
            }

            $pvUpd = $this->db->prepare(
                "UPDATE product_variants SET stock_qty = GREATEST(0, stock_qty - :qty), updated_at = NOW()
                 WHERE id = :variant_id AND {$this->tenantFilter()}"
            );
            $pvParams = ['qty' => $qty, 'variant_id' => $variantId];
            $this->bindTenant($pvParams);
            $pvUpd->execute($pvParams);
        } else {
            $stmt = $this->db->prepare(
                "SELECT id, qty_in_stock FROM inventory
                 WHERE product_id = :product_id AND variant_id IS NULL
                 AND is_deleted = 0 AND {$this->tenantFilter()}"
            );
            $params = ['product_id' => $productId];
            $this->bindTenant($params);
            $stmt->execute($params);
            $inv = $stmt->fetch();

            if (!$inv) {
                throw new \InvalidArgumentException('Inventory record not found.');
            }

            $afterQty = $beforeQty - $qty;

            $upd = $this->db->prepare(
                "UPDATE inventory SET qty_in_stock = :qty, last_updated = NOW(), updated_at = NOW()
                 WHERE id = :id AND {$this->tenantFilter()}"
            );
            $updParams = ['qty' => $afterQty, 'id' => $inv['id']];
            $this->bindTenant($updParams);
            $upd->execute($updParams);
        }

        $histStmt = $this->db->prepare(
            "INSERT INTO stock_history (
                tenant_id, product_id, variant_id, change_qty, qty_before, qty_after,
                reason, reference_type, reference_id, created_by, created_at, updated_at
             ) VALUES (
                :tenant_id, :product_id, :variant_id, :change_qty, :qty_before, :qty_after,
                :reason, 'sale', :reference_id, :created_by, NOW(), NOW()
             )"
        );
        $histStmt->execute([
            'tenant_id'    => $this->tenantId,
            'product_id'   => $productId,
            'variant_id'   => $variantId,
            'change_qty'   => -$qty,
            'qty_before'   => $beforeQty,
            'qty_after'    => $afterQty,
            'reason'       => $reason,
            'reference_id' => $saleId,
            'created_by'   => Auth::userId(),
        ]);
    }

    private function addCreditLedgerEntry(int $customerId, int $saleId, float $amount, string $saleNumber): void
    {
        $balanceStmt = $this->db->prepare(
            "SELECT balance_after FROM customer_ledger
             WHERE customer_id = :customer_id AND is_deleted = 0 AND {$this->tenantFilter()}
             ORDER BY id DESC LIMIT 1"
        );
        $params = ['customer_id' => $customerId];
        $this->bindTenant($params);
        $balanceStmt->execute($params);
        $row = $balanceStmt->fetch();
        $balance = $row ? (float) $row['balance_after'] : 0.0;

        $ledgerStmt = $this->db->prepare(
            "INSERT INTO customer_ledger (
                tenant_id, customer_id, sale_id, transaction_type, amount, balance_after,
                notes, created_by, created_at, updated_at
             ) VALUES (
                :tenant_id, :customer_id, :sale_id, 'sale', :amount, :balance_after,
                :notes, :created_by, NOW(), NOW()
             )"
        );
        $ledgerStmt->execute([
            'tenant_id'     => $this->tenantId,
            'customer_id'   => $customerId,
            'sale_id'       => $saleId,
            'amount'        => $amount,
            'balance_after' => $balance + $amount,
            'notes'         => 'Credit sale ' . $saleNumber,
            'created_by'    => Auth::userId(),
        ]);
    }

    private function validateCreditLimit(int $customerId, float $amount): void
    {
        $stmt = $this->db->prepare(
            "SELECT credit_limit FROM customers
             WHERE id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = ['id' => $customerId];
        $this->bindTenant($params);
        $stmt->execute($params);
        $customer = $stmt->fetch();

        if (!$customer) {
            throw new \InvalidArgumentException('Customer not found.');
        }

        $creditLimit = (float) $customer['credit_limit'];
        if ($creditLimit <= 0) {
            return;
        }

        $balanceStmt = $this->db->prepare(
            "SELECT balance_after FROM customer_ledger
             WHERE customer_id = :customer_id AND is_deleted = 0 AND {$this->tenantFilter()}
             ORDER BY id DESC LIMIT 1"
        );
        $balParams = ['customer_id' => $customerId];
        $this->bindTenant($balParams);
        $balanceStmt->execute($balParams);
        $balRow = $balanceStmt->fetch();
        $currentBalance = $balRow ? (float) $balRow['balance_after'] : 0.0;

        if ($currentBalance + $amount > $creditLimit) {
            throw new \InvalidArgumentException('Credit limit exceeded for this customer.');
        }
    }

    private function getShopSlug(): string
    {
        $stmt = $this->db->prepare(
            "SELECT slug FROM shops WHERE id = :id AND is_deleted = 0"
        );
        $stmt->execute(['id' => $this->tenantId]);
        $row = $stmt->fetch();

        return $row['slug'] ?? 'SHOP';
    }
}
