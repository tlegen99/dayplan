<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Response;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $this->routes[$path][$method] = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $path = rtrim($request->path(), '/');
        $method = $request->method();

        if (!isset($this->routes[$path])) {
            throw new HttpException(404, 'Not found');
        }

        if (!isset($this->routes[$path][$method])) {
            throw new HttpException(405, 'Method not allowed');
        }

        $handler = $this->routes[$path][$method];

        return $handler($request);
    }
}
