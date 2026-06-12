<?php

namespace Modules\Auth;

use Core\Database;
use Core\Model;
use PDO;

class AuthModel extends Model
{
    protected string $table = 'users';

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, s.status as shop_status, s.name as shop_name, s.slug as shop_slug
             FROM users u
             JOIN shops s ON s.id = u.tenant_id
             WHERE u.email = :email AND u.is_deleted = 0 AND s.is_deleted = 0"
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findSuperAdminByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM super_admins WHERE email = :email AND is_deleted = 0"
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function recordLoginAttempt(string $email, string $ip): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)"
        );
        $stmt->execute(['email' => $email, 'ip' => $ip]);
    }

    public function getRecentAttempts(string $email, string $ip, int $minutes): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM login_attempts
             WHERE email = :email AND ip_address = :ip
             AND attempted_at > DATE_SUB(NOW(), INTERVAL :mins MINUTE)"
        );
        $stmt->execute(['email' => $email, 'ip' => $ip, 'mins' => $minutes]);
        return (int) $stmt->fetch()['cnt'];
    }

    public function clearLoginAttempts(string $email, string $ip): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM login_attempts WHERE email = :email AND ip_address = :ip"
        );
        $stmt->execute(['email' => $email, 'ip' => $ip]);
    }

    public function updateLastLogin(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $stmt->execute(['id' => $userId]);
    }

    public function registerShop(array $data): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO shops (name, slug, owner_name, phone, address, city, shop_type, status)
                 VALUES (:name, :slug, :owner_name, :phone, :address, :city, :shop_type, 'pending')"
            );
            $stmt->execute([
                'name'       => $data['shop_name'],
                'slug'       => $data['slug'],
                'owner_name' => $data['owner_name'],
                'phone'      => $data['phone'],
                'address'    => $data['address'],
                'city'       => $data['city'],
                'shop_type'  => $data['shop_type'],
            ]);
            $shopId = (int) $this->db->lastInsertId();

            $stmt = $this->db->prepare(
                "INSERT INTO users (tenant_id, name, email, phone, password, role, status)
                 VALUES (:tenant_id, :name, :email, :phone, :password, 'owner', 'active')"
            );
            $stmt->execute([
                'tenant_id' => $shopId,
                'name'      => $data['owner_name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'],
                'password'  => password_hash($data['password'], PASSWORD_BCRYPT),
            ]);

            $defaultSettings = [
                'invoice_header' => $data['shop_name'],
                'invoice_footer' => 'Thank you for your business!',
                'shop_phone'     => $data['phone'],
                'currency_symbol'=> 'Rs.',
                'receipt_copies' => '1',
                'default_payment_method' => 'cash',
            ];
            $sStmt = $this->db->prepare(
                "INSERT INTO settings (tenant_id, setting_key, setting_value) VALUES (:tid, :key, :val)"
            );
            foreach ($defaultSettings as $key => $val) {
                $sStmt->execute(['tid' => $shopId, 'key' => $key, 'val' => $val]);
            }

            $this->db->commit();
            return $shopId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function slugExists(string $slug): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM shops WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        return (int) $stmt->fetch()['cnt'] > 0;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM users WHERE email = :email AND is_deleted = 0"
        );
        $stmt->execute(['email' => $email]);
        return (int) $stmt->fetch()['cnt'] > 0;
    }
}
