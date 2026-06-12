<?php



namespace Modules\Users;



use Core\Auth;

use Core\Controller;



class UsersController extends Controller

{

    private UsersModel $model;



    private const ALLOWED_ROLES = ['manager', 'salesman'];



    public function __construct()

    {

        $this->model = new UsersModel();

    }



    public function index(): void

    {

        Auth::requireLogin();

        Auth::checkShopStatus();

        $this->requireOwner();

        $this->checkPermission('users', 'read');



        $search = trim((string) $this->input('search', ''));

        $page = max(1, (int) $this->input('page', 1));

        $users = $this->model->getAll($search, $page);



        $this->view('users/index', [

            'pageTitle' => 'Manage Users',

            'users'     => $users,

            'search'    => $search,

        ]);

    }



    public function create(): void

    {

        Auth::requireLogin();

        Auth::checkShopStatus();

        $this->requireOwner();

        $this->checkPermission('users', 'create');



        $this->view('users/create', [

            'pageTitle' => 'Add User',

            'old'       => [],

            'errors'    => [],

        ]);

    }



    public function store(): void

    {

        Auth::requireLogin();

        Auth::checkShopStatus();

        $this->requireOwner();

        $this->checkPermission('users', 'create');

        $this->requirePost();

        $this->verifyCsrf();



        $data = $this->collectUserInput(true);

        $errors = $this->validateUser($data, true);



        if ($errors) {

            $this->view('users/create', [

                'pageTitle' => 'Add User',

                'old'       => $data,

                'errors'    => $errors,

            ]);

            return;

        }



        $id = $this->model->create($data);

        $this->logActivity('create', 'users', $id, 'Created user: ' . $data['name']);

        Auth::flash('success', 'User created successfully.');

        $this->redirect('users');

    }



    public function edit(): void

    {

        Auth::requireLogin();

        Auth::checkShopStatus();

        $this->requireOwner();

        $this->checkPermission('users', 'update');



        $id = (int) $this->input('id', 0);

        $user = $this->model->findById($id);



        if (!$user || $user['role'] === 'owner') {

            Auth::flash('error', 'User not found or cannot be edited.');

            $this->redirect('users');

        }



        $this->view('users/edit', [

            'pageTitle' => 'Edit User',

            'user'      => $user,

            'errors'    => [],

        ]);

    }



    public function update(): void

    {

        Auth::requireLogin();

        Auth::checkShopStatus();

        $this->requireOwner();

        $this->checkPermission('users', 'update');

        $this->requirePost();

        $this->verifyCsrf();



        $id = (int) $this->input('id', 0);

        $user = $this->model->findById($id);



        if (!$user || $user['role'] === 'owner') {

            Auth::flash('error', 'User not found or cannot be edited.');

            $this->redirect('users');

        }



        $data = $this->collectUserInput(false);

        $errors = $this->validateUser($data, false, $id);



        if ($errors) {

            $this->view('users/edit', [

                'pageTitle' => 'Edit User',

                'user'      => array_merge($user, $data),

                'errors'    => $errors,

            ]);

            return;

        }



        if ($this->model->update($id, $data)) {

            $this->logActivity('update', 'users', $id, 'Updated user: ' . $data['name']);

            Auth::flash('success', 'User updated successfully.');

        } else {

            Auth::flash('error', 'Failed to update user.');

        }



        $this->redirect('users');

    }



    public function delete(): void

    {

        Auth::requireLogin();

        Auth::checkShopStatus();

        $this->requireOwner();

        $this->checkPermission('users', 'delete');

        $this->requirePost();

        $this->verifyCsrf();



        $id = (int) $this->input('id', 0);



        if ($id === Auth::userId()) {

            Auth::flash('error', 'You cannot delete your own account.');

            $this->redirect('users');

        }



        $user = $this->model->findById($id);



        if (!$user || $user['role'] === 'owner') {

            Auth::flash('error', 'User not found or cannot be deleted.');

            $this->redirect('users');

        }



        if ($this->model->deleteUser($id)) {

            $this->logActivity('delete', 'users', $id, 'Deleted user: ' . $user['name']);

            Auth::flash('success', 'User deleted successfully.');

        } else {

            Auth::flash('error', 'Failed to delete user.');

        }



        $this->redirect('users');

    }



    private function requireOwner(): void

    {

        if (Auth::role() !== 'owner') {

            Auth::flash('error', 'Only the shop owner can manage users.');

            $this->redirect('dashboard');

        }

    }



    private function collectUserInput(bool $isCreate): array

    {

        return [

            'name'     => trim((string) $this->input('name', '')),

            'email'    => trim((string) $this->input('email', '')),

            'phone'    => trim((string) $this->input('phone', '')),

            'role'     => $this->input('role', ''),

            'status'   => $this->input('status', 'active'),

            'password' => $this->input('password', ''),

        ];

    }



    private function validateUser(array $data, bool $isCreate, ?int $excludeId = null): array

    {

        $errors = [];



        if ($data['name'] === '') {

            $errors[] = 'Name is required.';

        }



        if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {

            $errors[] = 'A valid email is required.';

        } elseif ($this->model->emailExistsInTenant($data['email'], $excludeId)) {

            $errors[] = 'This email is already used in your shop.';

        }



        if (!in_array($data['role'], self::ALLOWED_ROLES, true)) {

            $errors[] = 'Invalid role. Only Manager or Salesman can be assigned.';

        }



        if (!in_array($data['status'], ['active', 'inactive'], true)) {

            $errors[] = 'Invalid status.';

        }



        if ($isCreate) {

            if (strlen($data['password']) < 8) {

                $errors[] = 'Password must be at least 8 characters.';

            }

        } elseif ($data['password'] !== '' && strlen($data['password']) < 8) {

            $errors[] = 'Password must be at least 8 characters.';

        }



        return $errors;

    }

}


