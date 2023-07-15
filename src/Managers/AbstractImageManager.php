<?php

namespace SimpleImageManager\Managers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleImageManager\Contracts\ImageManagerInterface;
use Spatie\Image\Image;

abstract class AbstractImageManager implements ImageManagerInterface
{
    /**
     * Use specific disk.
     *
     * @var string|null
     */
    public ?string $disk = null;

    /**
     * Clear empty directory after deletion files.
     * Warning: this function is memory and time-consuming when there can be too many files.
     *
     * @var bool
     */
    public bool $truncateDir = false;

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
     * List of deleted configuration formats.
     * Useful if you deleted some configuration but still files exists for old created files
     * and you need do delete these formats on delete file.
     *
     * @var array
     */
    public array $deletedFormats = [];

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
     * @param array $formats
     *
     * @return $this
     */
    public function setDeletedFormats(array $formats = []): static
    {
        $this->deletedFormats = $formats;

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
     * @param bool $truncateDir
     * @return $this
     */
    public function truncateDir(bool $truncateDir = true): static
    {
        $this->truncateDir = $truncateDir;

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

    public function upload(UploadedFile $image, ?string $fileName = null, ?string $oldFile = null): string
    {
        if ($oldFile) {
            $this->delete($oldFile);
        }

        $newFileName = $this->makeFileName($fileName);
        if (Str::endsWith($newFileName, ".{$image->extension()}")) {
            $newFileName = Str::beforeLast($newFileName, ".{$image->extension()}");
        }

        $tmpFile = rtrim(dirname($newFileName), '/') . '/.tmp';
        $this->storage()->put($tmpFile, '');
        $this->storage()->delete($tmpFile);

        $newFileExt = '.' . $image->extension();

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
     * Get files list with all formats to delete.
     *
     * @param string $fileName
     * @return array
     */
    protected function filesToDelete(string $fileName): array
    {
        if (!$fileName) {
            return [];
        }

        $filesToDelete = [
            $fileName,
        ];

        [$name, $extension] = $this->explodeFilename($fileName);

        foreach (array_keys($this->formats) as $format) {
            $filesToDelete[] = "{$name}-{$format}.{$extension}";
        }

        foreach ($this->deletedFormats as $format) {
            $filesToDelete[] = "{$name}-{$format}.{$extension}";
        }

        return array_unique($filesToDelete);
    }

    /**
     * Clear empty directory if this is required.
     *
     * @param string $fileName
     * @return bool
     */
    protected function truncateDirectory(string $fileName): bool
    {
        if (!$this->truncateDir) {
            return false;
        }

        $directoryName = dirname($fileName);

        if (
            !$directoryName ||
            !empty($this->storage()->allFiles($directoryName))
        ) {
            return false;
        }

        return $this->storage()->deleteDirectory($directoryName);
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
     * @inheritDoc
     */
    public function srcsetMap(): array
    {
        $map = [];

        if (is_array($this->original) &&
            !empty($this->original['srcset'])) {
            $map[''] = $this->original['srcset'];
        }

        foreach ($this->formats as $format => $configuration) {
            if (!empty($configuration['srcset'])) {
                $map[$format] = $configuration['srcset'];
            }
        }

        uasort($map, function ($a, $b) {
            return (int)$b - (int)$a;
        });

        return $map;
    }

    protected function explodeFilename(string $fileName)
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

    /**
     * @param string|null $fileName
     *
     * @return string
     */
    protected function makeFileName(?string $fileName = null): string
    {
        if (!$fileName) {
            return $this->prefix . Str::random(30);
        }

        return $this->prefix . $fileName;
    }

    protected function createOriginalFile(UploadedFile $image, string $newFileName, string $newFileExt)
    {
        $path = "{$newFileName}{$newFileExt}";
        if (!in_array($newFileExt, $this->immutableExtensions)) {
            $builder = Image::load($image->path());
            if (is_array($this->original) &&
                !empty($this->original['methods'])
            ) {
                foreach ($this->original['methods'] as $method => $attrs) {
                    call_user_func_array([$builder, $method], $attrs);
                }
            }

            $builder->save($this->storage()->path($path));
        } else {
            $this->storage()->put($path, $image->getContent());
        }
    }

    protected function createFormats(UploadedFile $image, string $newFileName, string $newFileExt)
    {
        if (!in_array($newFileExt, $this->immutableExtensions)) {
            foreach ($this->formats as $format => $configuration) {
                $path = "{$newFileName}-{$format}{$newFileExt}";

                $builder = Image::load($image->path());
                if (!empty($configuration['methods']) && is_array($configuration['methods'])) {
                    foreach ($configuration['methods'] as $method => $attrs) {
                        call_user_func_array([$builder, $method], $attrs);
                    }
                }
                $builder->save($this->storage()->path($path));
            }
        }
    }
}
