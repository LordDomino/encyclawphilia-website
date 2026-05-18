<?php
declare(strict_types=1);


// ROUTING
// Manually require dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Core/Router.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/UserController.php';
require_once __DIR__ . '/../src/Controllers/PagesNavigationController.php';

$router = new \Core\Router();

// Define Application Route Table
$router->get('/', 'PagesNavigationController@home');
$router->get('/home', 'PagesNavigationController@home');
$router->get('/browse', 'PagesNavigationController@browse');
$router->get('/about', 'PagesNavigationController@about');
$router->get('/login', 'PagesNavigationController@login');
$router->get('/ordinance', 'PagesNavigationController@ordinance');
$router->post('/login-submit', 'AuthController@handleLoginSubmit');
$router->post('/signup-submit', 'AuthController@handleSignupSubmit');
$router->post('/react', 'UserController@handleOrdinanceReact');
$router->get('/profile/{id}', 'AuthController@showProfile'); // Dynamic parameter route

// Catch incoming request context
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Execute Routing Matrix
$router->dispatch($requestUri, $requestMethod);