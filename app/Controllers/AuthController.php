<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Support\Auth;

class AuthController extends Controller
{
    /**
     * Show the login form (GET /login)
     */
    public function showLoginForm(): void
    {
        // If already logged in, send them to admin dashboard (adjust route as needed)
        if (Auth::check()) {
            $this->redirect('/admin');
        }

        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        $this->view('auth/login', compact('errors'));
    }

    /**
     * Handle login submission (POST /login)
     */
    public function login(): void
    {
        
        $identifier = trim($_POST['email'] ?? $_POST['username'] ?? '');
        $password   = trim($_POST['password'] ?? '');

        $errors = [];

        if ($identifier === '' || $password === '') {
            $errors[] = 'Email/username and password are required.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $this->redirect('/login');
        }

        if (!Auth::attempt($identifier, $password)) {
            $_SESSION['errors'] = ['Invalid credentials.'];
            $this->redirect('/login');
        }

        // Successful login → send to admin dashboard
        $this->redirect('/dashboard');
    }

    /**
     * Logout and redirect to login (GET /logout or POST /logout)
     */
    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login');
    }
}
