<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autoload
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) require $file;
});

// Load .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $_ENV[$k] = $v;
    }
}

use App\Core\Router;
use App\Database\Database;
use App\Middleware\AuthMiddleware;
use App\Controllers\Api\TelemetryController;
use App\Controllers\Admin\GeofenceController;


Database::init([
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'name' => $_ENV['DB_NAME'] ?? 'team_transport',
    'user' => $_ENV['DB_USER'] ?? 'TEAMUSER',
    'pass' => $_ENV['DB_PASS'] ?? 'TEAM1234',
    'port' => (int)($_ENV['DB_PORT'] ?? 3306),
]);

$router = new Router();
$auth = new AuthMiddleware();

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
$router->get('/login',  'AuthController@loginForm');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/
$router->get('/',          'DashboardController@index', [$auth]);
$router->get('/dashboard', 'DashboardController@index', [$auth]);
$router->get('/profile',   'ProfileController@index',  [$auth]);

/*
|--------------------------------------------------------------------------
| ADMIN DASHBOARD
|--------------------------------------------------------------------------
*/
$router->get('/admin', 'Admin\\AdminDashboardController@index', [$auth]);

/*
|--------------------------------------------------------------------------
| ADMIN: USERS
|--------------------------------------------------------------------------
*/
$router->get('/admin/users',               'Admin\\UserController@index',  [$auth]);
$router->get('/admin/users/create',        'Admin\\UserController@create', [$auth]);
$router->post('/admin/users/create',       'Admin\\UserController@store',  [$auth]);
$router->get('/admin/users/edit/{id}',     'Admin\\UserController@edit',   [$auth]);
$router->post('/admin/users/edit/{id}',    'Admin\\UserController@update', [$auth]);
$router->post('/admin/users/delete/{id}',  'Admin\\UserController@delete', [$auth]);

/*
|--------------------------------------------------------------------------
| ADMIN: CUSTOMERS
|--------------------------------------------------------------------------
*/
$router->get('/admin/customers',             'Admin\\CustomerAdminController@index',  [$auth]);
$router->get('/admin/customers/create',      'Admin\\CustomerAdminController@create', [$auth]);
$router->post('/admin/customers/create',     'Admin\\CustomerAdminController@store',  [$auth]);
$router->get('/admin/customers/edit/{id}',   'Admin\\CustomerAdminController@edit',   [$auth]);
$router->post('/admin/customers/edit/{id}',  'Admin\\CustomerAdminController@update', [$auth]);
$router->post('/admin/customers/delete/{id}','Admin\\CustomerAdminController@delete', [$auth]);

/*
|--------------------------------------------------------------------------
| ADMIN: DRIVERS
|--------------------------------------------------------------------------
*/
$router->get('/admin/drivers',                  'Admin\\DriverAdminController@index',          [$auth]);
$router->get('/admin/drivers/view/{id}',        'Admin\\DriverAdminController@profile',        [$auth]);
$router->get('/admin/drivers/assign-vehicle/{id}', 'Admin\\DriverAdminController@assignVehicleForm', [$auth]);
$router->post('/admin/drivers/assign-vehicle/{id}','Admin\\DriverAdminController@assignVehicleSave', [$auth]);

/*
|--------------------------------------------------------------------------
| TELEMETRY (REAL-TIME MAP MUST BE FIRST)
|--------------------------------------------------------------------------
*/
$router->get('/admin/vehicles/map', 'Api\\TelemetryController@liveMap', [$auth]);
$router->get('/api/telemetry/latest', 'Api\\TelemetryController@latest');
$router->get('/api/telemetry/history/{id}', 'Api\\TelemetryController@history');
$router->post('/api/telemetry/ingest', 'Api\\TelemetryController@ingest');


/*
|--------------------------------------------------------------------------
| ADMIN: VEHICLES (STATIC FIRST, DYNAMIC LAST)
|--------------------------------------------------------------------------
*/
$router->get('/admin/vehicles',                     'Admin\\VehicleAdminController@index', [$auth]);
$router->get('/admin/vehicles/create',              'Admin\\VehicleAdminController@create', [$auth]);
$router->post('/admin/vehicles/create',             'Admin\\VehicleAdminController@store',  [$auth]);
$router->get('/admin/vehicles/edit/{id}',           'Admin\\VehicleAdminController@edit',   [$auth]);
$router->post('/admin/vehicles/edit/{id}',          'Admin\\VehicleAdminController@update', [$auth]);
$router->get('/admin/vehicles/delete/{id}',         'Admin\\VehicleAdminController@confirmDelete', [$auth]);
$router->post('/admin/vehicles/delete/{id}',        'Admin\\VehicleAdminController@delete',        [$auth]);
$router->post('/admin/vehicles/{id}/assign-driver', 'Admin\\VehicleAdminController@assignDriver', [$auth]);

// ADD THIS BEFORE THE DYNAMIC ROUTES:
$router->get('/admin/vehicles/{id}/maintenance', 'Admin\\VehicleAdminController@maintenance', [$auth]);

// DYNAMIC ROUTES LAST (or they will steal /map)
$router->get('/admin/vehicles/view/{id}',       'Admin\\VehicleAdminController@profile', [$auth]);
$router->get('/admin/vehicles/{id}',            'Admin\\VehicleAdminController@profile', [$auth]);

/*
|--------------------------------------------------------------------------
| ADMIN: VEHICLES Maintenance
|--------------------------------------------------------------------------
*/
$router->get('/admin/vehicles/{id}/maintenance/create', 'Admin\\VehicleAdminController@maintenanceCreate', [$auth]);
$router->post('/admin/vehicles/{id}/maintenance/create', 'Admin\\VehicleAdminController@maintenanceStore', [$auth]);

/*
|--------------------------------------------------------------------------
| GPS MANUAL UPDATE
|--------------------------------------------------------------------------
*/
$router->post('/admin/vehicles/{id}/gps-update', 'Admin\\GpsController@update', [$auth]);
$router->get('/admin/vehicles/{id}/gps-history', 'Admin\\GpsController@history', [$auth]);

// Geofences CRUD
$router->get('/admin/geofences', 'Admin\\GeofenceController@index', [$auth]);
$router->get('/admin/geofences/create', 'Admin\\GeofenceController@create', [$auth]);
$router->post('/admin/geofences/store', 'Admin\\GeofenceController@store', [$auth]);

// Edit (ID IN URL)
$router->get('/admin/geofences/edit',      'Admin\\GeofenceController@edit', [$auth]);
$router->get('/admin/geofences/edit/{id}', 'Admin\\GeofenceController@edit', [$auth]);


// Update POST
$router->post('/admin/geofences/update/{id}', 'Admin\\GeofenceController@update', [$auth]);

// Delete (POST)
$router->post('/admin/geofences/delete/{id}', 'Admin\\GeofenceController@delete', [$auth]);

// Alerts history
$router->get('/admin/geofences/alerts', 'Admin\\GeofenceController@alerts', [$auth]);


/*
|--------------------------------------------------------------------------
| ADMIN: LOADS
|--------------------------------------------------------------------------
*/
$router->get('/admin/loads', 'Admin\\LoadAdminController@index', [$auth]);

/*
|--------------------------------------------------------------------------
| ADMIN: SETTINGS
|--------------------------------------------------------------------------
*/
$router->get('/admin/settings', 'Admin\\SettingsAdminController@index', [$auth]);

/*
|--------------------------------------------------------------------------
| LOAD CONTROLLER (NON-ADMIN)
|--------------------------------------------------------------------------
*/
$router->get('/loads',          'LoadController@index',        [$auth]);
$router->get('/loads/view',     'LoadController@show',         [$auth]);
$router->get('/loads/create',   'LoadController@create',       [$auth]);
$router->post('/loads',         'LoadController@store',        [$auth]);
$router->get('/loads/edit',     'LoadController@edit',         [$auth]);
$router->post('/loads/update',  'LoadController@update',       [$auth]);
$router->post('/loads/status',  'LoadController@updateStatus', [$auth]);
$router->post('/loads/bulk',    'LoadController@bulkActions',  [$auth]);

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
