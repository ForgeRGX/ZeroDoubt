<?php
declare(strict_types=1);

final class Router
{
    /** @var array<int, array{0:string,1:string,2:array}> */
    private array $routes = [];

    public function add(string $method, string $path, array $handler): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $path) . '$#';
        $this->routes[] = [$method, $regex, $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = $path === '/' ? '/' : rtrim($path, '/');

        foreach ($this->routes as [$m, $regex, $handler]) {
            if ($m === $method && preg_match($regex, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                [$class, $action] = $handler;
                (new $class())->$action(...$params);
                return;
            }
        }
        http_response_code(404);
        View::render('errors/404', ['title' => 'Página no encontrada']);
    }
}
