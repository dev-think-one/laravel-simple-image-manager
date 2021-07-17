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
     * @var bool|array
     */
    public $responsive = false;

    /**
     * @var array
     */
    public array $formats = [];

    /**
     * @var array
     */
    public array $deletedFormats = [];


    public function __construct(array $configs)
    {
        if (empty($configs['disk']) || !is_string($configs['disk'])) {
            throw new \InvalidArgumentException("Driver configuration has not key 'disk'");
        }

        $this->disk = $configs['disk'];

        if (isset($configs['original']) && (is_array($configs['original']) || is_bool($configs['original']))) {
            $this->original = $configs['original'];
        }

        if (isset($configs['responsive']) && (is_array($configs['responsive']) || is_bool($configs['responsive']))) {
            $this->responsive = $configs['responsive'];
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

        list($name, $extension) = $this->explodeFilename($fileName);

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
            list($name, $extension) = $this->explodeFilename($fileName);

            $fileName = "{$name}-{$format}.{$extension}";
        }


        return  $this->storage()->delete($fileName);
    }

    /**
     * @inheritDoc
     */
    public function path(string $fileName, ?string $format): ?string
    {
        if (!$fileName) {
            return null;
        }
        if ($format) {
            list($name, $extension) = $this->explodeFilename($fileName);

            $fileName = "{$name}-{$format}.{$extension}";
        }

        return $this->storage()->path($fileName);
    }

    /**
     * @inheritDoc
     */
    public function url(string $fileName, ?string $format): ?string
    {
        if (!$fileName) {
            return null;
        }
        if ($format) {
            list($name, $extension) = $this->explodeFilename($fileName);

            $fileName = "{$name}-{$format}.{$extension}";
        }

        return $this->storage()->url($fileName);
    }

    protected function explodeFilename(string $fileName)
    {
        $extension     = pathinfo($fileName, PATHINFO_EXTENSION);

        $name = Str::beforeLast($fileName, ".{$extension}");

        return [$name, $extension];
    }

    protected function storage()
    {
        return Storage::disk($this->disk);
    }

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
        if (is_array($this->original) &&
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
            if (!empty($configuration['methods']) && is_array($configuration['methods'])) {
                foreach ($configuration['methods'] as $method => $attrs) {
                    call_user_func_array([ $builder, $method ], $attrs);
                }
            }
            $builder->save($formatPath);
        }
    }
}
