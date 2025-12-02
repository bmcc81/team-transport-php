<?php
declare(strict_types=1);

session_start();

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Simple env loader
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $_ENV[$k] = $v;
    }
}

use App\Core\Router;
use App\Database\Database;
use App\Middleware\AuthMiddleware;

Database::init([
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'name' => $_ENV['DB_NAME'] ?? 'team_transport',
    'user' => $_ENV['DB_USER'] ?? 'TEAMUSER',
    'pass' => $_ENV['DB_PASS'] ?? 'TEAM1234',
    'port' => (int)($_ENV['DB_PORT'] ?? 3306),
]);

$router = new Router();

// Public routes
$router->get('/login',  'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

$auth = new AuthMiddleware();

// Profile route
$router->get('/profile', 'ProfileController@index', [$auth]);

// Protected dashboard
$router->get('/',          'DashboardController@index', [$auth]);
$router->get('/dashboard', 'DashboardController@index', [$auth]);

// Admin Panel Home
$router->get('/admin', 'Admin\\AdminDashboardController@index');

// ===========================
// ADMIN USER MANAGEMENT
// ===========================
$router->get('/admin/users', 'Admin\\UserController@index');
$router->get('/admin/users/create', 'Admin\\UserController@create');
$router->post('/admin/users/store', 'Admin\\UserController@store');
$router->get('/admin/users/edit/{id}', 'Admin\\UserController@edit');
$router->post('/admin/users/update/{id}', 'Admin\\UserController@update');
$router->post('/admin/users/delete/{id}', 'Admin\\UserController@delete');

// Loads
$router->get('/loads',          'LoadController@index',        [$auth]);
$router->get('/loads/view',     'LoadController@show',         [$auth]);
$router->get('/loads/create',   'LoadController@create',       [$auth]);
$router->post('/loads',         'LoadController@store',        [$auth]);
$router->get('/loads/edit',     'LoadController@edit',         [$auth]);
$router->post('/loads/update',  'LoadController@update',       [$auth]);
$router->post('/loads/status',  'LoadController@updateStatus', [$auth]);
$router->post('/loads/bulk',    'LoadController@bulkActions',  [$auth]);



/**
 * Admin User Management Routes
 * Only accessible by admin role
 */

// Admin panel (all routes protected by admin guard in Router)
// -----------------------------
// ADMIN PANEL ROUTES
// -----------------------------
$router->get('/admin', 'Admin\\AdminDashboardController@index', [$auth]);

// Users
$router->get('/admin/users', 'Admin\\UserController@index', [$auth]);
$router->get('/admin/users/create', 'Admin\\UserController@create', [$auth]);
$router->post('/admin/users/create', 'Admin\\UserController@store', [$auth]);
$router->get('/admin/users/edit/{id}', 'Admin\\UserController@edit', [$auth]);
$router->post('/admin/users/edit/{id}', 'Admin\\UserController@update', [$auth]);
$router->post('/admin/users/delete/{id}', 'Admin\\UserController@delete', [$auth]);

// Customers
// Customers CRUD
$router->get('/admin/customers',            'Admin\\CustomerAdminController@index',  [$auth]);
$router->get('/admin/customers/create',     'Admin\\CustomerAdminController@create', [$auth]);
$router->post('/admin/customers/create',    'Admin\\CustomerAdminController@store',  [$auth]);
$router->get('/admin/customers/edit/{id}',  'Admin\\CustomerAdminController@edit',   [$auth]);
$router->post('/admin/customers/edit/{id}', 'Admin\\CustomerAdminController@update', [$auth]);
$router->post('/admin/customers/delete/{id}','Admin\\CustomerAdminController@delete',[$auth]);

// Drivers
$router->get('/admin/drivers', 'Admin\\DriverAdminController@index', [$auth]);
$router->get('/admin/drivers/view/{id}', 'Admin\\DriverAdminController@profile', [$auth]);

// Vehicles
$router->get('/admin/vehicles', 'Admin\\VehicleAdminController@index', [$auth]);

// Loads (admin super-view)
$router->get('/admin/loads', 'Admin\\LoadAdminController@index', [$auth]);

// Settings
$router->get('/admin/settings', 'Admin\\SettingsAdminController@index', [$auth]);

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

