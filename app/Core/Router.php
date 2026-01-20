<?php

namespace App\Core;

class Router
{
    protected static $routes = [];

    public static function get($uri, $controller)
    {
        self::$routes['GET'][$uri] = $controller;
    }

    public static function post($uri, $controller)
    {
        self::$routes['POST'][$uri] = $controller;
    }

    public static function dispatch($uri)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Strip query string and trailing slash
        $uri = trim($uri, '/');
        if ($uri === '') {
            $uri = '/'; // Root
        } else {
            $uri = '/' . $uri; // Ensure leading slash
        }

        if (array_key_exists($uri, self::$routes[$method])) {
            $controller = self::$routes[$method][$uri];
            
            // If controller is a closure/function
            if (is_callable($controller)) {
                call_user_func($controller);
                return;
            }

            // If controller is "Class@method" string
            if (is_string($controller) && strpos($controller, '@') !== false) {
                list($class, $method) = explode('@', $controller);
                $fullClass = "App\\Controllers\\$class";
                $instance = new $fullClass();
                $instance->$method();
                return;
            }
        }

        // 404 Not Found
        http_response_code(404);
        echo "404 - Page Not Found (" . htmlspecialchars($uri) . ")";
    }
}
