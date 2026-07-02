<?php

declare(strict_types=1);

namespace Nova\Http;

use Nova\Storage\UploadFile;
use Nova\Support\Arr;

/**
 * Represents the current HTTP request.
 */
final class Request
{
    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        private readonly array $query,
        private readonly array $body,
        private readonly array $files,
        private readonly array $cookies,
        private readonly array $server,
        private array $routeParams = []
    ) {
    }

    public static function capture(): self
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            '/' . trim($uri, '/'),
            $_GET,
            $_POST ?: self::jsonBody(),
            self::normalizeFiles($_FILES),
            $_COOKIE,
            $_SERVER
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->uri === '/' ? '/' : '/' . trim($this->uri, '/');
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        $data = array_replace_recursive($this->query, $this->body);
        return $key === null ? $data : Arr::get($data, $key, $default);
    }

    public function all(): array
    {
        return $this->input();
    }

    public function only(array|string $keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $data = [];

        foreach ($keys as $key) {
            $data[$key] = $this->input((string) $key);
        }

        return $data;
    }

    public function except(array|string $keys): array
    {
        $keys = array_flip(is_array($keys) ? $keys : func_get_args());

        return array_diff_key($this->all(), $keys);
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        $contentType = $this->server['CONTENT_TYPE'] ?? '';
        $data = str_contains($contentType, 'application/json') ? $this->body : [];

        return $key === null ? $data : Arr::get($data, $key, $default);
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->query : Arr::get($this->query, $key, $default);
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));

        return $this->server[$serverKey] ?? $this->server[strtoupper(str_replace('-', '_', $key))] ?? $default;
    }

    public function file(string $key): UploadFile|array|null
    {
        return $this->files[$key] ?? null;
    }

    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function routeParams(): array
    {
        return $this->routeParams;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function expectsJson(): bool
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        $requestedWith = $this->server['HTTP_X_REQUESTED_WITH'] ?? '';
        $contentType = $this->server['CONTENT_TYPE'] ?? '';

        return str_contains($accept, 'application/json')
            || strcasecmp($requestedWith, 'XMLHttpRequest') === 0
            || str_contains($contentType, 'application/json')
            || str_starts_with($this->path(), '/api/');
    }

    private static function jsonBody(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (!str_contains($contentType, 'application/json')) {
            return [];
        }

        return json_decode((string) file_get_contents('php://input'), true) ?: [];
    }

    private static function normalizeFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                $normalized[$key] = [];
                foreach ($file['name'] as $index => $name) {
                    $normalized[$key][$index] = new UploadFile($name, $file['tmp_name'][$index], $file['type'][$index], $file['error'][$index], $file['size'][$index]);
                }
                continue;
            }
            $normalized[$key] = new UploadFile($file['name'], $file['tmp_name'], $file['type'], $file['error'], $file['size']);
        }
        return $normalized;
    }
}
