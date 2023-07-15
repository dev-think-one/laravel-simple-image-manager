<?php

namespace SimpleImageManager\Managers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\Image\Image;

trait CanCreate
{
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

    protected function ensureDirectoryExists(string $fileName): void
    {
        // Be sure directories tree created
        $tmpFile = rtrim(dirname($fileName), '/') . '/.tmp-' . Str::uuid();
        $this->storage()->put($tmpFile, '');
        $this->storage()->delete($tmpFile);
    }

    protected function createOriginalFile(UploadedFile $image, string $newFileName, string $newFileExt): void
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

    protected function createFormats(UploadedFile $image, string $newFileName, string $newFileExt): void
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
