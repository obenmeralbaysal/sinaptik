<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                $handler = $route['handler'];
                
                if (is_array($handler)) {
                    $controllerName = $handler[0];
                    $methodName = $handler[1];
                    $controller = new $controllerName();
                    $controller->$methodName();
                } else {
                    $handler();
                }
                return;
            }
        }

        http_response_code(404);
        echo "404 Not Found";
    }
}
