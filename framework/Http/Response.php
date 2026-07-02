<?php

declare(strict_types=1);

namespace Nova\Http;

/**
 * Represents a basic HTTP response.
 */
class Response
{
    private array $cookies = [];

    public function __construct(private string $content = '', private int $status = 200, private array $headers = [])
    {
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function header(string $name, string $value): self
    {
        $this->assertHeader($name, $value);
        $this->headers[$name] = $value;
        return $this;
    }

    public function cookie(string $name, string $value, array $options = []): self
    {
        if ($name === '' || preg_match('/[=,;\\s\\r\\n]/', $name) === 1) {
            throw new ResponseException('Cookie name contains invalid characters.');
        }

        $this->cookies[] = [$name, $value, $options];
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            $this->assertHeader($name, $value);
            header($name . ': ' . $value);
        }
        foreach ($this->cookies as [$name, $value, $options]) {
            setcookie($name, $value, $options);
        }
        echo $this->content;
    }

    private function assertHeader(string $name, string $value): void
    {
        if ($name === '' || preg_match('/^[A-Za-z0-9!#$%&\'*+.^_`|~-]+$/', $name) !== 1) {
            throw new ResponseException('Header name contains invalid characters.');
        }

        if (str_contains($value, "\r") || str_contains($value, "\n")) {
            throw new ResponseException('Header value contains invalid characters.');
        }
    }
}
