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
        if (empty($configs['disk']) || !is_string($configs['disk'])) {
            throw new \InvalidArgumentException("Driver configuration has not key 'disk'");
        }

        $this->disk = $configs['disk'];

        if (isset($configs['original']) && (is_array($configs['original']) || is_bool($configs['original']))) {
            $this->original = $configs['original'];
        }

        if (isset($configs['formats']) && is_array($configs['formats'])) {
            $this->formats = $configs['formats'];
        }

        if (isset($configs['deletedFormats']) && is_array($configs['deletedFormats'])) {
            $this->deletedFormats = $configs['deletedFormats'];
        }

        if (!empty($configs['prefix']) && is_string($configs['prefix'])) {
            $this->prefix = $configs['prefix'];
        }

        if (!empty($configs['immutable_extensions']) && is_array($configs['immutable_extensions'])) {
            $this->immutableExtensions = $configs['immutable_extensions'];
        }
    }

    public function upload(UploadedFile $image, ?string $fileName = null, ?string $oldFile = null): string
    {
        if ($oldFile) {
            $this->delete($oldFile);
        }

        $newFileName = $this->makeFileName($fileName);
        $this->storage()->createDir(dirname($newFileName));

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
        if (!$fileName) {
            return false;
        }

        $filesToDelete = [
            $fileName,
        ];

        [ $name, $extension ] = $this->explodeFilename($fileName);

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
            [ $name, $extension ] = $this->explodeFilename($fileName);

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
            [ $name, $extension ] = $this->explodeFilename($fileName);

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
            [ $name, $extension ] = $this->explodeFilename($fileName);

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
                $map[ $format ] = $configuration['srcset'];
            }
        }

        sort($map);

        return $map;
    }

    protected function explodeFilename(string $fileName)
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        $name = Str::beforeLast($fileName, ".{$extension}");

        return [ $name, $extension ];
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
        $builder = Image::load($image->path());
        if (!in_array($newFileExt, $this->immutableExtensions) &&
             is_array($this->original) &&
             !empty($this->original['methods'])
        ) {
            foreach ($this->original['methods'] as $method => $attrs) {
                call_user_func_array([ $builder, $method ], $attrs);
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
                 !empty($configuration['methods']) && is_array($configuration['methods'])) {
                foreach ($configuration['methods'] as $method => $attrs) {
                    call_user_func_array([ $builder, $method ], $attrs);
                }
            }
            $builder->save($formatPath);
        }
    }
}
