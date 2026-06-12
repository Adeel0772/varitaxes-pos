<?php

namespace Modules\Invoices;

use Core\Model;

class InvoicesModel extends Model
{
    protected string $table = 'sales';

    public function getSaleForInvoice(int $saleId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, u.name AS salesman_name, u.phone AS salesman_phone,
                    c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address
             FROM sales s
             INNER JOIN users u ON u.id = s.salesman_id AND u.is_deleted = 0
             LEFT JOIN customers c ON c.id = s.customer_id AND c.is_deleted = 0
             WHERE s.id = :id AND s.is_deleted = 0 AND {$this->tenantFilter('s')}"
        );
        $params = ['id' => $saleId];
        $this->bindTenant($params);
        $stmt->execute($params);
        $sale = $stmt->fetch();
        if (!$sale) {
            return null;
        }

        $itemsStmt = $this->db->prepare(
            "SELECT si.*, p.barcode
             FROM sale_items si
             LEFT JOIN products p ON p.id = si.product_id AND p.is_deleted = 0
             WHERE si.sale_id = :sale_id AND si.is_deleted = 0 AND {$this->tenantFilter('si')}
             ORDER BY si.id"
        );
        $itemParams = ['sale_id' => $saleId];
        $this->bindTenant($itemParams);
        $itemsStmt->execute($itemParams);
        $items = $itemsStmt->fetchAll();

        $shopStmt = $this->db->prepare(
            "SELECT id, name, slug, owner_name, phone, address, city, logo, invoice_format
             FROM shops WHERE id = :id AND is_deleted = 0"
        );
        $shopStmt->execute(['id' => $this->tenantId]);
        $shop = $shopStmt->fetch() ?: null;

        $settingsStmt = $this->db->prepare(
            "SELECT setting_key, setting_value FROM settings
             WHERE tenant_id = :tenant_id AND is_deleted = 0"
        );
        $settingsStmt->execute(['tenant_id' => $this->tenantId]);
        $settings = [];
        foreach ($settingsStmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return [
            'sale'     => $sale,
            'items'    => $items,
            'shop'     => $shop,
            'customer' => $sale['customer_id'] ? [
                'name'    => $sale['customer_name'],
                'phone'   => $sale['customer_phone'],
                'address' => $sale['customer_address'],
            ] : null,
            'salesman' => [
                'name'  => $sale['salesman_name'],
                'phone' => $sale['salesman_phone'],
            ],
            'settings' => $settings,
        ];
    }

    public function markInvoicePrinted(int $saleId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE sales SET invoice_printed = 1, updated_at = NOW()
             WHERE id = :id AND is_deleted = 0 AND {$this->tenantFilter()}"
        );
        $params = ['id' => $saleId];
        $this->bindTenant($params);
        return $stmt->execute($params);
    }
}
