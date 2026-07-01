<?php

declare(strict_types=1);

namespace Nova\Http;

final class UploadedFile
{
    public function __construct(
        public readonly string $name,
        public readonly string $tmpName,
        public readonly string $type,
        public readonly int $error,
        public readonly int $size
    ) {
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->tmpName);
    }

    public function store(string $directory, ?string $name = null): string
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $target = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . ($name ?: uniqid('', true) . '-' . basename($this->name));
        if (!move_uploaded_file($this->tmpName, $target)) {
            throw new \RuntimeException('Unable to store uploaded file.');
        }

        return $target;
    }
}
