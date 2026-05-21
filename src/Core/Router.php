<?php
namespace App\Core;

class Router
{
    // Holds the compiled route matrix segregated by HTTP method
    protected array $routes = [];

    /**
     * Registers a route and compiles its path into a regular expression.
     */
    public function add(string $method, string $route, string $handler): void
    {
        // Convert a route template like '/profile/{id}' into a valid Regex pattern
        // Example output: '#^/profile/(?P<id>[a-zA-Z0-9_-]+)$#'
        $pattern = preg_replace('/\{([a-zA-Z0-9_-]+)\}/', '(?P<$1>[a-zA-Z0-9_-]+)', $route);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[strtoupper($method)][$pattern] = $handler;
    }

    // Helper shortcuts for common HTTP verbs
    public function get(string $route, string $handler): void { $this->add('GET', $route, $handler); }
    public function post(string $route, string $handler): void { $this->add('POST', $route, $handler); }

    /**
     * Resolves the incoming URL against the registered matrix.
     */
    public function dispatch(string $requestUri, string $requestMethod): void
    {
        // 1. Sanitize and normalize the URL path
        $urlPath = parse_url($requestUri, PHP_URL_PATH);
        $method = strtoupper($requestMethod);

        if (!isset($this->routes[$method])) {
            $this->abort(404);
        }

        // 2. Iterate through patterns registered under this specific HTTP method
        foreach ($this->routes[$method] as $pattern => $handler) {
            if (preg_match($pattern, $urlPath, $matches)) {
                
                // Extract only the named capture groups corresponding to parameters (like 'id')
                $parameters = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $this->execute($handler, $parameters);
                return;
            }
        }

        // No pattern matched the URL path
        $this->abort(404);
    }

    /**
     * Dynamically instantiates the target controller and invokes the specified action.
     */
    protected function execute(string $handler, array $parameters): void
    {
        // Parse the 'Controller@method' syntax
        list($controllerName, $action) = explode('@', $handler);
        
        $controllerClass = "App\\Controllers\\" . $controllerName;

        if (class_exists($controllerClass)) {
            $controllerInstance = new $controllerClass();

            if (method_exists($controllerInstance, $action)) {
                // Safely execute the method and pass variables as ordered arguments
                call_user_func_array([$controllerInstance, $action], $parameters);
                return;
            }
        }

        $this->abort(500); // Controller or Method misconfiguration
    }

    protected function abort(int $code): void
    {
        http_response_code($code);
        // Securely handle rendering error layouts
        echo "Error {$code}: Application stopped.";
        exit;
    }
}