<?php

declare(strict_types=1);

namespace Nova\Http;

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
        $this->headers[$name] = $value;
        return $this;
    }

    public function cookie(string $name, string $value, array $options = []): self
    {
        $this->cookies[] = [$name, $value, $options];
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }
        foreach ($this->cookies as [$name, $value, $options]) {
            setcookie($name, $value, $options);
        }
        echo $this->content;
    }
}
