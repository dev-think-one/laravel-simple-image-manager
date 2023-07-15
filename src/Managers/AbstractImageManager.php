<?php

namespace SimpleImageManager\Managers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleImageManager\Contracts\ImageManagerInterface;

abstract class AbstractImageManager implements ImageManagerInterface
{
    use HasSrcSet;
    use CanCreate;
    use CanDelete;

    /**
     * Use specific disk.
     *
     * @var string|null
     */
    public ?string $disk = null;

    /**
     * Add prefix to files. Can be directory ot just filename prefix.
     *
     * @var string
     */
    public string $prefix = '';

    /**
     * Save original file.
     *
     * @var array|null
     */
    public ?array $original = null;

    /**
     * Formats configurations list for creation.
     *
     * @var array
     */
    public array $formats = [];

    /**
     * Files extensions lists what should not be updated/cropped. Like svg or gif.
     *
     * @var array
     */
    public array $immutableExtensions = [];


    public function __construct(array $configs)
    {
        $this->setDisk($configs['disk'] ?? null);

        if (array_key_exists('original', $configs)) {
            $this->setOriginal($configs['original']);
        }

        if (array_key_exists('formats', $configs)) {
            $this->setFormats($configs['formats']);
        }

        if (array_key_exists('deletedFormats', $configs)) {
            $this->setDeletedFormats($configs['deletedFormats']);
        }

        if (array_key_exists('prefix', $configs)) {
            $this->setPrefix((string)$configs['prefix']);
        }

        if (array_key_exists('truncateDir', $configs)) {
            $this->truncateDir($configs['truncateDir']);
        }

        /** @deprecated */
        if (array_key_exists('immutable_extensions', $configs)) {
            $this->setImmutableExtensions($configs['immutable_extensions']);
        }

        if (array_key_exists('immutableExtensions', $configs)) {
            $this->setImmutableExtensions($configs['immutableExtensions']);
        }
    }

    /**
     * @param string|null $disk
     *
     * @return $this
     */
    public function setDisk(?string $disk = null): static
    {
        if (empty($disk) || !is_string($disk)) {
            throw new \InvalidArgumentException("Driver configuration has not key 'disk'");
        }

        $this->disk = $disk;

        return $this;
    }

    /**
     * @param array|null $original
     *
     * @return $this
     */
    public function setOriginal(?array $original = null): static
    {
        $this->original = $original;

        return $this;
    }

    /**
     * @param array $formats
     *
     * @return $this
     */
    public function setFormats(array $formats = []): static
    {
        $this->formats = $formats;

        return $this;
    }

    /**
     * @param array $immutableExtensions
     *
     * @return $this
     */
    public function setImmutableExtensions(array $immutableExtensions = []): static
    {
        $this->immutableExtensions = $immutableExtensions;

        return $this;
    }

    /**
     * @param ?string $prefix
     *
     * @return $this
     */
    public function setPrefix(?string $prefix = null): static
    {
        $this->prefix = (string)$prefix;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function upload(UploadedFile $image, ?string $fileName = null, ?string $oldFile = null): string
    {
        if ($oldFile) {
            $this->delete($oldFile);
        }

        $newFileName = $this->makeFileName($fileName);

        // Clear extension if exists
        if (Str::endsWith($newFileName, ".{$image->extension()}")) {
            $newFileName = Str::beforeLast($newFileName, ".{$image->extension()}");
        }

        $this->ensureDirectoryExists($newFileName);

        $newFileExt = ".{$image->extension()}";

        if ($this->original) {
            $this->createOriginalFile($image, $newFileName, $newFileExt);
        }

        $this->createFormats($image, $newFileName, $newFileExt);

        return "{$newFileName}{$newFileExt}";
    }

    /**
     * @inheritDoc
     */
    public function delete(string $fileName): bool
    {
        $filesToDelete = $this->filesToDelete($fileName);

        if (empty($filesToDelete)) {
            return false;
        }

        $isDeleted = $this->storage()->delete($filesToDelete);

        $this->truncateDirectory($fileName);

        return $isDeleted;
    }

    /**
     * @inheritDoc
     */
    public function deleteSingle(string $fileName, ?string $format = null): bool
    {
        if (!$fileName) {
            return false;
        }
        if ($format) {
            [$name, $extension] = $this->explodeFilename($fileName);

            $fileName = "{$name}-{$format}.{$extension}";
        }


        return $this->storage()->delete($fileName);
    }

    /**
     * @inheritDoc
     */
    public function path(string $fileName, ?string $format = null): ?string
    {
        if (!$fileName) {
            return null;
        }
        if ($format) {
            [$name, $extension] = $this->explodeFilename($fileName);
            if (!in_array(".{$extension}", $this->immutableExtensions)) {
                $fileName = "{$name}-{$format}.{$extension}";
            }
        }

        return $this->storage()->path($fileName);
    }

    /**
     * @inheritDoc
     */
    public function url(string $fileName, ?string $format = null): ?string
    {
        if (!$fileName) {
            return null;
        }
        if ($format) {
            [$name, $extension] = $this->explodeFilename($fileName);
            if (!in_array(".{$extension}", $this->immutableExtensions)) {
                $fileName = "{$name}-{$format}.{$extension}";
            }
        }

        return $this->storage()->url($fileName);
    }

    /**
     * Returns name and extension of filename.
     *
     * @param string $fileName
     * @return array
     */
    protected function explodeFilename(string $fileName): array
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        $name = Str::beforeLast($fileName, ".{$extension}");

        return [$name, $extension];
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function storage(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($this->disk);
    }




}
