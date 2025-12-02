<?php
namespace App\Controllers;

use App\Core\Controller;

class ProfileController extends Controller
{
    public function index(): void
    {
        $user = [
            'username'  => $_SESSION['username'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
            'role'      => $_SESSION['role'] ?? '',
        ];

        $this->view('profile/index', ['user' => $user]);
    }
}
