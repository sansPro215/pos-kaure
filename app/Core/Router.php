<?php

namespace App\Core;

class Router
{
    private static array $routes = [];
    private static string $basePath = '';

    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/');
    }

    public static function getBasePath(): string
    {
        if (self::$basePath !== '') {
            return self::$basePath;
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        if ($scriptDir === '/' || $scriptDir === '.') {
            $scriptDir = '';
        }

        if (str_ends_with($scriptDir, '/public')) {
            $parentDir = substr($scriptDir, 0, -7);
            if (str_starts_with($requestUri, $scriptDir)) {
                self::$basePath = $scriptDir;
            } else {
                self::$basePath = $parentDir;
            }
        } else {
            self::$basePath = $scriptDir;
        }

        return self::$basePath;
    }

    public static function url(string $path = ''): string
    {
        $base = self::getBasePath();
        $path = '/' . ltrim($path, '/');
        if ($path === '/' && !empty($base)) {
            return $base . '/';
        }
        return $base . $path;
    }

    public static function get(string $path, array|callable $handler, array $middlewares = []): void
    {
        self::addRoute('GET', $path, $handler, $middlewares);
    }

    public static function post(string $path, array|callable $handler, array $middlewares = []): void
    {
        self::addRoute('POST', $path, $handler, $middlewares);
    }

    private static function addRoute(string $method, string $path, array|callable $handler, array $middlewares): void
    {
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        // Convert {param} to regex pattern
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        self::$routes[] = [
            'method' => $method,
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares,
        ];
    }

    public static function resolve(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        // Parse path from URI
        $parsedUrl = parse_url($requestUri);
        $uriPath = $parsedUrl['path'] ?? '/';

        // Strip base path
        $basePath = self::getBasePath();
        if (!empty($basePath) && strpos($uriPath, $basePath) === 0) {
            $uriPath = substr($uriPath, strlen($basePath));
        }

        $uriPath = '/' . trim($uriPath, '/');
        if ($uriPath !== '/') {
            $uriPath = rtrim($uriPath, '/');
        }

        // Handle direct /index.php access
        if ($uriPath === '/index.php') {
            $uriPath = '/';
        }

        foreach (self::$routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            if (preg_match($route['pattern'], $uriPath, $matches)) {
                // Filter string keys for route parameters
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                // Run middlewares
                foreach ($route['middlewares'] as $middleware) {
                    self::runMiddleware($middleware);
                }

                // Call handler
                $handler = $route['handler'];
                if (is_callable($handler)) {
                    call_user_func_array($handler, $params);
                    return;
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$controllerClass, $action] = $handler;
                    if (class_exists($controllerClass)) {
                        $controller = new $controllerClass();
                        if (method_exists($controller, $action)) {
                            call_user_func_array([$controller, $action], $params);
                            return;
                        }
                    }
                }

                http_response_code(500);
                echo "Handler method not found for route: {$uriPath}";
                return;
            }
        }

        // Not Found
        http_response_code(404);
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['status' => false, 'message' => 'Halaman tidak ditemukan (404)']);
            return;
        }

        $viewPath = __DIR__ . '/../../views/layouts/404.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo '<div style="font-family:sans-serif; text-align:center; padding:50px;">
                <h1>404</h1>
                <p>Halaman tidak ditemukan.</p>
                <a href="' . self::url('/') . '">Kembali ke Beranda</a>
            </div>';
        }
    }

    private static function runMiddleware(string $middleware): void
    {
        $parts = explode(':', $middleware, 2);
        $name = $parts[0];
        $param = $parts[1] ?? null;

        switch ($name) {
            case 'auth':
                \App\Middleware\AuthMiddleware::handle();
                break;
            case 'role':
                \App\Middleware\RoleMiddleware::handle($param);
                break;
            case 'csrf':
                \App\Middleware\AuthMiddleware::verifyCsrf();
                break;
        }
    }
}
