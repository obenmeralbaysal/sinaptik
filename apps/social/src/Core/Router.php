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
            $pattern = "@^" . $route['path'] . "$@D";

            if ($route['method'] === $method && preg_match($pattern, $path, $matches)) {
                array_shift($matches); // Remove full match

                $handler = $route['handler'];

                if (is_array($handler)) {
                    $controllerName = $handler[0];
                    $methodName = $handler[1];
                    $controller = new $controllerName();

                    // Pass matches as arguments to the controller method
                    call_user_func_array([$controller, $methodName], $matches);
                } else {
                    call_user_func_array($handler, $matches);
                }
                return;
            }
        }

        http_response_code(404);
        echo "404 Not Found";
    }
}
