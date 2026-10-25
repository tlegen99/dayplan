<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Container\Container;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Response;

final class Router
{
    private array $routes = [];

    public function __construct(private readonly Container $container) {}

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[$this->normalizePath($path)][$method] = $handler;
    }

    private function normalizePath(string $path): string
    {
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    public function dispatch(Request $request): Response
    {
        $path = $this->normalizePath($request->path());
        $method = $request->method();

        if (!isset($this->routes[$path])) {
            throw new HttpException(404, 'Not found');
        }

        if (!isset($this->routes[$path][$method])) {
            throw new HttpException(405, 'Method not allowed');
        }

        $handler = $this->routes[$path][$method];

        if (is_array($handler) && is_string($handler[0])) {
            [$class, $action] = $handler;

            $controller = $this->container->get($class);
            return $controller->$action($request);
        }

        return $handler($request);
    }
}
