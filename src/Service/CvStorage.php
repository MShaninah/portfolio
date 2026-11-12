<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Stores the single CV/resume PDF outside the document root.
 *
 * The file lives in a directory that survives deployments (on the server this
 * is symlinked to shared/var/storage), so uploading a new CV does not have to
 * be repeated after every release.
 */
final readonly class CvStorage
{
    public const string FILENAME = 'cv.pdf';
    /** Sidecar holding the original upload name, so downloads keep it. */
    private const string NAME_FILE = 'cv.name';
    public const int MAX_BYTES = 5 * 1024 * 1024;

    public function __construct(
        #[Autowire('%app.cv_dir%')]
        private string $directory,
    ) {
    }

    public function path(): string
    {
        return $this->directory.'/'.self::FILENAME;
    }

    public function exists(): bool
    {
        return is_file($this->path());
    }

    /**
     * Whether an upload could actually be written.
     *
     * Worth checking explicitly: the PHP process and the directory owner often
     * differ (a container running as www-data against a host-mounted volume,
     * or a deploy user that created shared/var/storage), and the failure would
     * otherwise surface only as an opaque 500.
     */
    public function isWritable(): bool
    {
        if (is_dir($this->directory)) {
            return is_writable($this->directory);
        }

        $parent = \dirname($this->directory);

        return is_dir($parent) && is_writable($parent);
    }

    /**
     * The filename offered to the browser, falling back to a sensible default
     * if the sidecar is missing.
     */
    public function downloadName(): string
    {
        $sidecar = $this->directory.'/'.self::NAME_FILE;
        $stored = is_file($sidecar) ? trim((string) file_get_contents($sidecar)) : '';

        return '' !== $stored ? $stored : 'CV.pdf';
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        if (!$this->exists()) {
            return null;
        }

        $mtime = @filemtime($this->path());

        return false === $mtime ? null : new DateTimeImmutable('@'.$mtime);
    }

    /**
     * Replaces the stored CV. The previous file is overwritten in place.
     *
     * @throws FileException if the directory cannot be written
     */
    public function store(UploadedFile $file): void
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0o775, true) && !is_dir($this->directory)) {
            throw new FileException(\sprintf('Cannot create the CV directory "%s".', $this->directory));
        }

        if (!is_writable($this->directory)) {
            throw new FileException(\sprintf('The CV directory "%s" is not writable by %s.', $this->directory, get_current_user()));
        }

        // Captured before move(): the UploadedFile loses its client metadata
        // once it has been moved.
        $original = $file->getClientOriginalName();

        $file->move($this->directory, self::FILENAME);

        $safe = preg_replace('/[^A-Za-z0-9._ -]/', '_', $original) ?? '';
        if ('' === $safe || !str_ends_with(strtolower($safe), '.pdf')) {
            $safe = 'CV.pdf';
        }
        file_put_contents($this->directory.'/'.self::NAME_FILE, $safe);
    }
}
