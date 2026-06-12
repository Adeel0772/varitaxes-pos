<?php



namespace Modules\Settings;



use Core\Model;



class SettingsModel extends Model

{

    protected string $table = 'settings';



    public function getAllSettings(): array

    {

        $stmt = $this->db->prepare(

            "SELECT setting_key, setting_value FROM settings

             WHERE tenant_id = :tenant_id AND is_deleted = 0"

        );

        $stmt->execute(['tenant_id' => $this->tenantId]);



        $settings = [];

        foreach ($stmt->fetchAll() as $row) {

            $settings[$row['setting_key']] = $row['setting_value'];

        }



        return $settings;

    }



    public function get(string $key, ?string $default = null): ?string

    {

        $stmt = $this->db->prepare(

            "SELECT setting_value FROM settings

             WHERE tenant_id = :tenant_id AND setting_key = :key AND is_deleted = 0

             LIMIT 1"

        );

        $stmt->execute(['tenant_id' => $this->tenantId, 'key' => $key]);

        $row = $stmt->fetch();



        return $row ? $row['setting_value'] : $default;

    }



    public function set(string $key, ?string $value): bool

    {

        $existing = $this->get($key);



        if ($existing !== null) {

            $stmt = $this->db->prepare(

                "UPDATE settings SET setting_value = :value, updated_at = NOW()

                 WHERE tenant_id = :tenant_id AND setting_key = :key AND is_deleted = 0"

            );

            return $stmt->execute([

                'value'     => $value,

                'tenant_id' => $this->tenantId,

                'key'       => $key,

            ]);

        }



        $stmt = $this->db->prepare(

            "INSERT INTO settings (tenant_id, setting_key, setting_value)

             VALUES (:tenant_id, :key, :value)"

        );

        return $stmt->execute([

            'tenant_id' => $this->tenantId,

            'key'       => $key,

            'value'     => $value,

        ]);

    }



    public function updateBatch(array $data): bool

    {

        $this->db->beginTransaction();

        try {

            foreach ($data as $key => $value) {

                if (!$this->set($key, $value === null ? null : (string) $value)) {

                    throw new \RuntimeException('Failed to save setting: ' . $key);

                }

            }

            $this->db->commit();

            return true;

        } catch (\Exception $e) {

            $this->db->rollBack();

            return false;

        }

    }



    public function getShop(): ?array

    {

        $stmt = $this->db->prepare(

            "SELECT id, name, logo, invoice_format FROM shops

             WHERE id = :id AND is_deleted = 0"

        );

        $stmt->execute(['id' => $this->tenantId]);

        $row = $stmt->fetch();

        return $row ?: null;

    }



    public function updateShopLogo(string $path): bool

    {

        $stmt = $this->db->prepare(

            "UPDATE shops SET logo = :logo, updated_at = NOW()

             WHERE id = :tenant_id AND is_deleted = 0"

        );

        return $stmt->execute(['logo' => $path, 'tenant_id' => $this->tenantId]);

    }



    public function updateShopInvoiceFormat(string $format): bool

    {

        if (!in_array($format, ['a4', 'carbon'], true)) {

            return false;

        }



        $stmt = $this->db->prepare(

            "UPDATE shops SET invoice_format = :format, updated_at = NOW()

             WHERE id = :tenant_id AND is_deleted = 0"

        );

        return $stmt->execute(['format' => $format, 'tenant_id' => $this->tenantId]);

    }

}


