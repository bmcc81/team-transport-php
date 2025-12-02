<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class SettingsAdminController extends Controller
{
    public function index(): void
    {
        // later we can load system_settings from DB
        $settings = [
            'app_name' => 'Team Transport',
            'environment' => 'local',
            'timezone' => 'America/Toronto'
        ];

        $this->view('admin/settings/index', ['settings' => $settings]);
    }
}
