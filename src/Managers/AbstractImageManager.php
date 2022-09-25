<?php

namespace SimpleImageManager\Managers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleImageManager\Contracts\ImageManagerInterface;
use Spatie\Image\Image;

abstract class AbstractImageManager implements ImageManagerInterface
{
    public string $disk;

    public string $prefix = '';

    /**
     * @var bool|array
     */
    public $original = false;

    /**
     * @var array
     */
    public array $formats = [];

    /**
     * @var array
     */
    public array $deletedFormats = [];

    /**
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
            $this->setPrefix($configs['prefix']);
        }

        if (array_key_exists('immutable_extensions', $configs)) {
            $this->setImmutableExtensions($configs['immutable_extensions']);
        }
    }

    /**
     * @param  string|null  $disk
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
     * @param  bool|array  $original
     *
     * @return $this
     */
    public function setOriginal(bool|array $original = false): static
    {
        $this->original = $original;

        return $this;
    }

    /**
     * @param  array  $formats
     *
     * @return $this
     */
    public function setFormats(array $formats): static
    {
        $this->formats = $formats;

        return $this;
    }

    /**
     * @param  array  $formats
     *
     * @return $this
     */
    public function setDeletedFormats(array $formats = []): static
    {
        $this->deletedFormats = $formats;

        return $this;
    }

    /**
     * @param  array  $immutableExtensions
     *
     * @return $this
     */
    public function setImmutableExtensions(array $immutableExtensions = []): static
    {
        $this->immutableExtensions = $immutableExtensions;

        return $this;
    }

    /**
     * @param  string  $prefix
     *
     * @return $this
     */
    public function setPrefix(string $prefix = ''): static
    {
        $this->prefix = $prefix;

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

        $tmpFile = rtrim(dirname($newFileName), '/').'/.tmp';
        $this->storage()->put($tmpFile, '');
        $this->storage()->delete($tmpFile);

        $newFileExt = '.'.$image->extension();

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
        if (!$fileName) {
            return false;
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

        return $this->storage()->delete(array_unique($filesToDelete));
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

            $fileName = "{$name}-{$format}.{$extension}";
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

            $fileName = "{$name}-{$format}.{$extension}";
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
            return (int) $b - (int) $a;
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
     * @param  string|null  $fileName
     *
     * @return string
     */
    protected function makeFileName(?string $fileName = null): string
    {
        if (!$fileName) {
            return $this->prefix.Str::random(30);
        }

        return $this->prefix.$fileName;
    }

    protected function createOriginalFile(UploadedFile $image, string $newFileName, string $newFileExt)
    {
        $builder = Image::load($image->path());
        if (!in_array($newFileExt, $this->immutableExtensions) &&
            is_array($this->original)                          &&
            !empty($this->original['methods'])
        ) {
            foreach ($this->original['methods'] as $method => $attrs) {
                call_user_func_array([$builder, $method], $attrs);
            }
        }

        $builder->save($this->storage()->path("{$newFileName}{$newFileExt}"));
    }

    protected function createFormats(UploadedFile $image, string $newFileName, string $newFileExt)
    {
        foreach ($this->formats as $format => $configuration) {
            $formatPath = $this->storage()->path("{$newFileName}-{$format}{$newFileExt}");
            $builder    = Image::load($image->path());
            if (!in_array($newFileExt, $this->immutableExtensions) &&
                !empty($configuration['methods'])                  && is_array($configuration['methods'])) {
                foreach ($configuration['methods'] as $method => $attrs) {
                    call_user_func_array([$builder, $method], $attrs);
                }
            }
            $builder->save($formatPath);
        }
    }
}
