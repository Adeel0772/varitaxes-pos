<?php



namespace Modules\Users;



use Core\Model;



class UsersModel extends Model

{

    protected string $table = 'users';



    public function getAll(string $search, int $page): array

    {

        $sql = "SELECT id, tenant_id, name, email, phone, role, status, last_login, created_at, updated_at

                FROM users

                WHERE is_deleted = 0 AND tenant_id = :tenant_id";

        $params = ['tenant_id' => $this->tenantId];



        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['name', 'email', 'phone'], $search);
            $sql .= $likeSql;
            $params = array_merge($params, $likeParams);
        }



        $sql .= " ORDER BY FIELD(role, 'owner', 'manager', 'salesman'), name ASC";



        $countSql = "SELECT COUNT(*) as total FROM users

                     WHERE is_deleted = 0 AND tenant_id = :tenant_id";

        $countParams = ['tenant_id' => $this->tenantId];



        if ($search !== '') {
            [$likeSql, $likeParams] = $this->orLikeClause('search', ['name', 'email', 'phone'], $search);
            $countSql .= $likeSql;
            $countParams = array_merge($countParams, $likeParams);
        }



        return $this->paginate($sql, $params, $page, $countSql, $countParams);

    }



    public function findById(int $id): ?array

    {

        $stmt = $this->db->prepare(

            "SELECT id, tenant_id, name, email, phone, role, status, last_login, created_at, updated_at

             FROM users

             WHERE id = :id AND tenant_id = :tenant_id AND is_deleted = 0"

        );

        $stmt->execute(['id' => $id, 'tenant_id' => $this->tenantId]);

        $row = $stmt->fetch();

        return $row ?: null;

    }



    public function create(array $data): int

    {

        $stmt = $this->db->prepare(

            "INSERT INTO users (tenant_id, name, email, phone, password, role, status)

             VALUES (:tenant_id, :name, :email, :phone, :password, :role, :status)"

        );

        $stmt->execute([

            'tenant_id' => $this->tenantId,

            'name'      => $data['name'],

            'email'     => $data['email'],

            'phone'     => $data['phone'] ?? null,

            'password'  => password_hash($data['password'], PASSWORD_BCRYPT),

            'role'      => $data['role'],

            'status'    => $data['status'] ?? 'active',

        ]);



        return (int) $this->db->lastInsertId();

    }



    public function update(int $id, array $data): bool

    {

        $fields = [

            'name'   => $data['name'],

            'email'  => $data['email'],

            'phone'  => $data['phone'] ?? null,

            'role'   => $data['role'],

            'status' => $data['status'] ?? 'active',

        ];



        $sql = "UPDATE users SET name = :name, email = :email, phone = :phone,

                role = :role, status = :status, updated_at = NOW()";

        $params = array_merge($fields, ['id' => $id, 'tenant_id' => $this->tenantId]);



        if (!empty($data['password'])) {

            $sql .= ", password = :password";

            $params['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

        }



        $sql .= " WHERE id = :id AND tenant_id = :tenant_id AND is_deleted = 0 AND role != 'owner'";



        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);

    }



    public function emailExistsInTenant(string $email, ?int $excludeId = null): bool

    {

        $sql = "SELECT COUNT(*) as cnt FROM users

                WHERE email = :email AND tenant_id = :tenant_id AND is_deleted = 0";

        $params = ['email' => $email, 'tenant_id' => $this->tenantId];



        if ($excludeId !== null) {

            $sql .= " AND id != :exclude_id";

            $params['exclude_id'] = $excludeId;

        }



        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        return (int) $stmt->fetch()['cnt'] > 0;

    }



    public function deleteUser(int $id): bool

    {

        $stmt = $this->db->prepare(

            "UPDATE users SET is_deleted = 1, updated_at = NOW()

             WHERE id = :id AND tenant_id = :tenant_id AND is_deleted = 0 AND role != 'owner'"

        );

        return $stmt->execute(['id' => $id, 'tenant_id' => $this->tenantId]);

    }

}


