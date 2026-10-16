<?php

declare(strict_types=1);

namespace Framework\Config;

final class EnvLoader
{
    public function __construct(private readonly string $projectRoot) {}

    public function load(): void
    {
        $filePath = rtrim($this->projectRoot, '/') . '/.env';

        if (!file_exists($filePath)) {
            return;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Cannot read .env file: $filePath");
        }

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

        if (str_starts_with($value, '"')) {
            $end = strpos($value, '"', 1);
            if ($end === false) {
                throw new \RuntimeException("Unclosed quote in .env: $value");
            }
            $value = substr($value, 1, $end - 1);
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
        $pattern = '/(?<!\\\\)\$(?:(?P<name>[a-zA-Z_][a-zA-Z0-9_]*)|{(?P<name_braced>[a-zA-Z_][a-zA-Z0-9_]*)})/';
        return preg_replace_callback($pattern, function ($matches) {
            $name = !empty($matches['name']) ? $matches['name'] : ($matches['name_braced'] ?? '');

            if (!$name) {
                return '';
            }

            if (isset($_ENV[$name])) {
                return $_ENV[$name];
            }

            if (isset($_SERVER[$name])) {
                return $_SERVER[$name];
            }

            $envValue = getenv($name);
            return $envValue !== false ? $envValue : '';
        }, $value);
    }
}
