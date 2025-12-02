<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }

        $error = $_SESSION['flash_error'] ?? null;
        $old   = $_SESSION['flash_old'] ?? [];

        unset($_SESSION['flash_error'], $_SESSION['flash_old']);

        $this->view('auth/login', [
            'error' => $error,
            'old'   => $old,
        ]);
    }

    public function login(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $_SESSION['flash_old'] = ['username' => $username];

        if ($username === '' || $password === '') {
            $_SESSION['flash_error'] = 'Please enter both username and password.';
            $this->redirect('/login');
        }

        $user = User::findByUsername($username);
        if (!$user) {
            $_SESSION['flash_error'] = 'Invalid credentials.';
            $this->redirect('/login');
        }

        $valid = false;
        if (!empty($user['pwd'])) {
            $valid = password_verify($password, $user['pwd']);
        } elseif (!empty($user['password'])) {
            $valid = hash('sha256', $password) === $user['password'];
        }

        if (!$valid) {
            $_SESSION['flash_error'] = 'Invalid credentials.';
            $this->redirect('/login');
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'] ?? $user['email'] ?? '';
        $_SESSION['role'] = $user['role'] ?? 'user';

        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
        $this->redirect('/login');
    }
}
