<?php
namespace App\Controllers;

use App\Core\Controller;

class ProfileController extends Controller
{
    public function index(): void
    {
        $user = [
            'username'  => $_SESSION['user']['username'] ?? '',
            'full_name' => $_SESSION['user']['full_name'] ?? '',
            'role'      => $_SESSION['user']['role'] ?? '',
        ];

        $this->view('profile/index', ['user' => $user]);
    }
}
