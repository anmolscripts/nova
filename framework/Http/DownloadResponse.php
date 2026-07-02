<?php

declare(strict_types=1);

namespace Nova\Http;

use Nova\Storage\Disk;

/**
 * Streams a stored file as an HTTP download response.
 */
final class DownloadResponse extends Response
{
    public function __construct(
        private readonly Disk $disk,
        private readonly string $path,
        private readonly ?string $name = null,
        private readonly bool $inline = false
    ) {
        parent::__construct('', 200);
    }

    public function send(): void
    {
        $name = $this->name ?: basename($this->path);
        $name = str_replace(["\r", "\n", '"', '\\'], '_', $name);
        header('Content-Type: ' . $this->disk->mime($this->path));
        header('Content-Length: ' . $this->disk->size($this->path));
        header('Content-Disposition: ' . ($this->inline ? 'inline' : 'attachment') . '; filename="' . $name . '"');
        echo $this->disk->get($this->path);
    }
}
