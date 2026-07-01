<?php

declare(strict_types=1);

namespace Nova\Http;

use Nova\Support\Arr;

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

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->query : Arr::get($this->query, $key, $default);
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function file(string $key): UploadedFile|array|null
    {
        return $this->files[$key] ?? null;
    }

    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function expectsJson(): bool
    {
        $accept = $this->server['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json') || str_starts_with($this->path(), '/api/');
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
                    $normalized[$key][$index] = new UploadedFile($name, $file['tmp_name'][$index], $file['type'][$index], $file['error'][$index], $file['size'][$index]);
                }
                continue;
            }
            $normalized[$key] = new UploadedFile($file['name'], $file['tmp_name'], $file['type'], $file['error'], $file['size']);
        }
        return $normalized;
    }
}
