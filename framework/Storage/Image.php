<?php

declare(strict_types=1);

namespace Nova\Storage;

/**
 * Reads and transforms local image files.
 */
final class Image
{
    private ?\GdImage $image = null;
    private string $mime;

    public function __construct(private string $path)
    {
        if (!is_file($path)) {
            throw new StorageException('Image file does not exist.');
        }

        $this->mime = function_exists('mime_content_type') ? (mime_content_type($path) ?: 'application/octet-stream') : 'application/octet-stream';
    }

    public function width(): int
    {
        return getimagesize($this->path)[0] ?? 0;
    }

    public function height(): int
    {
        return getimagesize($this->path)[1] ?? 0;
    }

    public function resize(int $width, ?int $height = null): self
    {
        $height ??= (int) round($this->height() * ($width / max(1, $this->width())));
        $this->resample($width, $height, 0, 0, $this->width(), $this->height());

        return $this;
    }

    public function crop(int $width, int $height, int $x = 0, int $y = 0): self
    {
        $this->resample($width, $height, $x, $y, $width, $height);

        return $this;
    }

    public function fit(int $width, int $height): self
    {
        $sourceWidth = $this->width();
        $sourceHeight = $this->height();
        $ratio = max($width / max(1, $sourceWidth), $height / max(1, $sourceHeight));
        $cropWidth = (int) round($width / $ratio);
        $cropHeight = (int) round($height / $ratio);
        $x = (int) max(0, ($sourceWidth - $cropWidth) / 2);
        $y = (int) max(0, ($sourceHeight - $cropHeight) / 2);
        $this->resample($width, $height, $x, $y, $cropWidth, $cropHeight);

        return $this;
    }

    public function compress(int $quality = 75): self
    {
        return $this->save(null, $quality);
    }

    public function convert(string $extension, ?string $target = null, int $quality = 85): string
    {
        $extension = strtolower(ltrim($extension, '.'));
        $target ??= preg_replace('/\.[^.]+$/', '.' . $extension, $this->path) ?: $this->path . '.' . $extension;
        $this->save($target, $quality, $extension);
        $this->path = $target;

        return $target;
    }

    public function save(?string $target = null, int $quality = 85, ?string $extension = null): self
    {
        $this->ensureGd();
        $target ??= $this->path;
        $extension ??= strtolower(pathinfo($target, PATHINFO_EXTENSION));

        match ($extension) {
            'jpg', 'jpeg' => imagejpeg($this->image, $target, $quality),
            'png' => imagepng($this->image, $target, max(0, min(9, (int) round((100 - $quality) / 10)))),
            'webp' => imagewebp($this->image, $target, $quality),
            default => throw new StoragePathException('Unsupported image format.'),
        };

        return $this;
    }

    private function resample(int $width, int $height, int $x, int $y, int $sourceWidth, int $sourceHeight): void
    {
        $this->ensureGd();
        $target = imagecreatetruecolor($width, $height);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $this->image, 0, 0, $x, $y, $width, $height, $sourceWidth, $sourceHeight);
        $this->image = $target;
    }

    private function ensureGd(): void
    {
        if (!extension_loaded('gd')) {
            throw new StorageException('GD extension is required for image transformations.');
        }

        if ($this->image instanceof \GdImage) {
            return;
        }

        $this->image = match ($this->mime) {
            'image/jpeg' => imagecreatefromjpeg($this->path),
            'image/png' => imagecreatefrompng($this->path),
            'image/webp' => imagecreatefromwebp($this->path),
            default => throw new StoragePathException('Unsupported image type.'),
        };
    }
}
