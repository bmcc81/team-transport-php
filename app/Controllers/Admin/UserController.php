<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function index(): void
    {
        $users = User::all();
        $this->view('admin/users/index', ['users' => $users]);
    }

    public function create(): void
    {
        $this->view('admin/users/create');
    }

    public function store(): void
    {
        $pwd = password_hash($_POST['password'], PASSWORD_DEFAULT);

        User::create([
            'username' => $_POST['username'],
            'pwd' => $pwd,
            'email' => $_POST['email'],
            'full_name' => $_POST['full_name'],
            'role' => $_POST['role'],
            'must_change_password' => isset($_POST['must_change_password']) ? 1 : 0,
            'created_by' => $_SESSION['user_id']
        ]);

        header("Location: /admin/users");
        exit;
    }

    public function edit($id)
{
    $user = User::find($id);

    if (!$user) {
        die("User not found");
    }

    $this->view('admin/users/edit', ['user' => $user]);
}

    public function update($id): void
    {
        $data = [
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'full_name' => $_POST['full_name'],
            'role' => $_POST['role'],
            'must_change_password' => isset($_POST['must_change_password']) ? 1 : 0
        ];

        // If password provided, hash it
        if (!empty($_POST['password'])) {
            $data['pwd'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        User::update((int)$id, $data);

        header("Location: /admin/users");
        exit;
    }

    public function delete($id): void
    {
        User::delete((int)$id);

        header("Location: /admin/users");
        exit;
    }
}
