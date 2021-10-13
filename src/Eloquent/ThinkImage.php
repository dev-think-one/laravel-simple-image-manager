<?php

namespace SimpleImageManager\Eloquent;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use SimpleImageManager\Contracts\ImageManagerInterface;
use SimpleImageManager\Facades\SimpleImageManager;

class ThinkImage
{
    protected ImageManagerInterface $manager;

    protected ?string $value;

    /**
     * Default fallback for path.
     *
     * @var string|null
     */
    protected ?string $defaultPath = null;

    /**
     * Default fallback for url.
     *
     * @var string|null
     */
    protected ?string $defaultUrl = null;

    public function __construct(string $driver, ?string $value = null)
    {
        $this->manager = SimpleImageManager::driver($driver);
        $this->value   = $value;
    }

    /**
     * @param string|null $value
     *
     * @return static
     */
    public function setValue(?string $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @param string|null $defaultPath
     *
     * @return static
     */
    public function setDefaultPath(?string $defaultPath): static
    {
        $this->defaultPath = $defaultPath;

        return $this;
    }

    /**
     * @param string|null $defaultUrl
     *
     * @return static
     */
    public function setDefaultUrl(?string $defaultUrl): static
    {
        $this->defaultUrl = $defaultUrl;

        return $this;
    }

    /**
     * Upload file to storage.
     *
     * @param UploadedFile $image
     * @param string|null $filename
     *
     * @return string|null Storage file name.
     */
    public function upload(UploadedFile $image, ?string $filename = null, ?string $oldFile = null): ?string
    {
        return $this->manager->upload($image, $filename, $oldFile);
    }

    /**
     * Delete file from storage.
     *
     * @return bool Storage file name.
     */
    public function delete(): bool
    {
        if (!$this->value) {
            return false;
        }

        return $this->manager->delete($this->value);
    }

    /**
     * Full path to file.
     *
     * @param string|null $format
     * @param string|null $default
     *
     * @return string|null
     */
    public function path(?string $format = null, ?string $default = null): ?string
    {
        if (!$this->value) {
            return $default ?? $this->defaultPath;
        }

        return $this->manager->path($this->value, $format) ?? ($default ?? $this->defaultPath);
    }

    /**
     * File url.
     *
     * @param string|null $format
     * @param string|null $default
     *
     * @return string|null
     */
    public function url(?string $format = null, ?string $default = null): ?string
    {
        if (!$this->value) {
            return $default ?? $this->defaultUrl;
        }

        return $this->manager->url((string) $this->value, $format) ?? ($default ?? $this->defaultUrl);
    }

    /**
     * Image tag.
     *
     * @param array $attrs
     * @param string|null $defaultUrl
     * @param bool $lazy
     *
     * @return string
     */
    public function img(array $attrs = [], ?string $defaultUrl = null, bool $lazy = true): string
    {
        $attrs = collect($attrs);

        if (!($src = $attrs->pull('src', $this->url(null, $defaultUrl)))) {
            return '';
        }

        $defaultSrc = $attrs->pull('defaultSrc', $src);
        $srcset     = $attrs->pull('srcset', collect($this->manager->srcsetMap())->map(fn ($i, $key) => trim($this->url($key, $defaultUrl) . " $i"))
                                                                                    ->filter()
                                                                                    ->implode(', '));
        $class      = $attrs->pull('class', '');
        $lazyClass  = trim($attrs->pull('lazyClass', 'lazy'));
        if ($lazy && !Str::contains($class, $lazyClass)) {
            $class = "{$lazyClass} {$class}";
        }

        $attributes = $attrs->map(fn ($i, $key) => "$key='{$i}'")->implode(' ');

        return View::make('simple-image-manager::blocks.think-image', compact('lazy', 'attributes', 'defaultSrc', 'src', 'srcset', 'class'))->render();
    }

    /**
     * Propagate call.
     *
     * @param string $method
     * @param array $attributes
     */
    public function __call($method, $attributes)
    {
        if (method_exists($this->manager, $method)) {
            return call_user_func_array([ $this->manager, $method ], $attributes);
        }

        throw new \BadMethodCallException("Method [{$method} not exists]");
    }
}
