<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Container\Container;
use Framework\Http\HttpException;
use Framework\Http\Request;
use Framework\Http\Response;

final class Router
{
    private array $staticRoutes = [];
    private array $dynamicRoutes = [];

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
        $path = $this->normalizePath($path);

        if (str_contains($path, '{')) {
            $this->dynamicRoutes[$path]['regex'] = $this->compile($path);
            $this->dynamicRoutes[$path]['handlers'][$method] = $handler;
            return;
        }

        $this->staticRoutes[$path][$method] = $handler;
    }

    private function normalizePath(string $path): string
    {
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    private function compile(string $path): string
    {
        // {id} в (?P<id>[^/]+)
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $path);

        return '#^' . $regex . '$#';
    }

    public function dispatch(Request $request): Response
    {
        $path = $this->normalizePath($request->path());
        $method = $request->method();

        if (isset($this->staticRoutes[$path])) {
            if (!isset($this->staticRoutes[$path][$method])) {
                throw new HttpException(405, 'Method not allowed');
            }

            return $this->callHandler($this->staticRoutes[$path][$method], $request);
        }

        foreach ($this->dynamicRoutes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            if (!isset($route['handlers'][$method])) {
                throw new HttpException(405, 'Method not allowed');
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            return $this->callHandler($route['handlers'][$method], $request->withAttributes($params));
        }

        throw new HttpException(404, 'Not found');
    }

    private function callHandler(callable|array $handler, Request $request): Response
    {
        if (is_array($handler) && is_string($handler[0])) {
            [$class, $action] = $handler;

            $controller = $this->container->get($class);
            return $controller->$action($request);
        }

        return $handler($request);
    }
}
