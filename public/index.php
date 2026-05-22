<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__) . '/src');
define('PUBLIC_ROOT', dirname(__DIR__) . '/public');
define('VIEWS_ROOT', APP_ROOT . '/Views');

// Basic PSR-4 Autoloader mapping "App\" namespace to the "src/" directory 
spl_autoload_register(function ($class) {
    if (file_exists($class)) {
        require_once $class;
        return;
    }

    $prefix = 'App\\';

    // Check if the class uses our root namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Move to next registered autoloader if not matching
    }

    $relative_class = substr($class, $len);

    // Map to the file path (e.g., APP_ROOT . "/Controllers/AuthController.php")
    $file = APP_ROOT . '/' . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$router = new App\Core\Router();

$sessionRoleId   = (int)($_SESSION['role_id'] ?? 1);
$_SESSION['is_admin'] = ($sessionRoleId === 1);

// Page routes
$router->get('/', 'PagesNavigationController@home');
$router->get('/home', 'PagesNavigationController@home');
$router->get('/browse', 'PagesNavigationController@browse');
$router->get('/about', 'PagesNavigationController@about');
$router->get('/login', 'PagesNavigationController@login');
$router->get('/ordinance', 'PagesNavigationController@ordinance');
$router->get('/account', 'PagesNavigationController@account');
$router->get('/dashboard', 'PagesNavigationController@adminDashboard'); // Only for administrator role


$router->post('/login-submit', 'AuthController@handleLoginSubmit');
$router->get('/logout-submit', 'AuthController@handleLogoutSubmit');
$router->post('/signup-submit', 'AuthController@handleSignupSubmit');

$router->post('/react', 'UserController@handleOrdinanceReact');
$router->get('/profile/{id}', 'AuthController@showProfile'); // Dynamic parameter route


$router->get('/add-ordinance', 'PagesNavigationController@addOrdinance');
$router->get('/edit-ordinance', 'DashboardController@editOrdinance');

$router->post('/store-ordinance', 'DashboardController@storeOrdinance');

// $router->get('/api/ordinances/search', 'DashboardController@searchOrdinance');
$router->get('/api/ordinances/search', 'OrdinanceApiController@search');
$router->get('/api/ordinances/meta',   'OrdinanceApiController@meta');

// Catch incoming request context
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Execute Routing Matrix
$router->dispatch($requestUri, $requestMethod);
