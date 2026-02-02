<?php

namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . "/../../views/$view.php";
        
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            echo "View $view not found";
        }
    }
}
