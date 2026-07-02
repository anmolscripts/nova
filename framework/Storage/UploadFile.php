<?php

declare(strict_types=1);

namespace Nova\Storage;

use Nova\Storage\Security\FileGuard;

/**
 * Represents an uploaded file and its storage lifecycle.
 */
class UploadFile
{
    private ?string $storedPath = null;
    private ?Disk $storedDisk = null;

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
        return $this->error === UPLOAD_ERR_OK && is_file($this->tmpName);
    }

    public function store(string $directory, string $disk = 'local'): self
    {
        return $this->storeAs($directory, $this->hashName(), $disk);
    }

    public function storeAs(string $directory, string $name, string $disk = 'local'): self
    {
        if (!$this->isValid()) {
            throw new StorageException('Uploaded file is not valid.');
        }

        $name = PathNormalizer::filename($name);
        FileGuard::assertSafeFilename($name);

        $directory = PathNormalizer::normalize($directory, true);
        $path = ltrim($directory . '/' . $name, '/');
        $storage = storage($disk);
        $storage->putFile($path, $this->tmpName);

        $this->storedDisk = $storage;
        $this->storedPath = $path;

        return $this;
    }

    public function temporary(): self
    {
        return $this->store('', 'temporary');
    }

    public function path(): ?string
    {
        return $this->storedPath;
    }

    public function fullPath(): ?string
    {
        return $this->storedPath && $this->storedDisk ? $this->storedDisk->path($this->storedPath) : null;
    }

    public function url(): ?string
    {
        if (!$this->storedPath || !$this->storedDisk) {
            return null;
        }

        try {
            return $this->storedDisk->url($this->storedPath);
        } catch (\RuntimeException) {
            return null;
        }
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    public function mime(): string
    {
        $mime = function_exists('mime_content_type') && is_file($this->tmpName) ? mime_content_type($this->tmpName) : false;

        return $mime ?: $this->type;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function hashName(?string $extension = null): string
    {
        $extension ??= $this->extension();
        $suffix = $extension !== '' ? '.' . $extension : '';

        return bin2hex(random_bytes(20)) . $suffix;
    }
}
