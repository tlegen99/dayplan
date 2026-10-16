<?php

namespace Framework\Config;

class EnvLoader
{
    private string $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = rtrim($projectRoot, '/');
    }

    public function loadEnv(string $defaultEnv = 'dev'): void
    {
        $path = $this->projectRoot . '/.env';

        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;

        if (null === $env) {
            $this->load($path);
            $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? $defaultEnv;
        }

        if ('test' !== $env && file_exists($p = $path . '.example')) {
            $this->load($p);
        }

        if (file_exists($p = $path . '.' . $env)) {
            $this->load($p);
        }

        if (file_exists($p = $path . '.' . $env . '.local')) {
            $this->load($p);
        }
    }

    public function load(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        $lines = preg_split('/\r\n|\n|\r/', $content);

        foreach ($lines as $line) {
            $line = ltrim($line);

            if ('' === $line || str_starts_with($line, '#')) {
                continue;
            }

            if (!preg_match('/^(export\s+)?(?P<name>[a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(?P<value>.*)/', $line, $matches)) {
                continue;
            }

            $name = $matches['name'];
            $value = rtrim($matches['value']);

            $value = $this->parseValue($value);

            if (isset($_ENV[$name]) || isset($_SERVER[$name])) {
                continue;
            }

            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            putenv("$name=$value");
        }
    }

    private function parseValue(string $value): string
    {
        if ('' === $value) {
            return '';
        }

        if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            return substr($value, 1, -1);
        }

        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = substr($value, 1, -1);
            $value = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $value);
            return $this->resolveVariables($value);
        }

        if (false !== $pos = strpos($value, ' #')) {
            $value = substr($value, 0, $pos);
        }
        $value = rtrim($value);

        return $this->resolveVariables($value);
    }

    private function resolveVariables(string $value): string
    {
        return preg_replace_callback('/(?<!\\\\)\$(?:(?P<name>[a-zA-Z_][a-zA-Z0-9_]*)|{(?P<name_braced>[a-zA-Z_][a-zA-Z0-9_]*)}做得)/', function ($matches) {
            $name = !empty($matches['name']) ? $matches['name'] : ($matches['name_braced'] ?? '');

            if (!$name) {
                return '';
            }

            return $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name) ?: '';
        }, $value);
    }
}
