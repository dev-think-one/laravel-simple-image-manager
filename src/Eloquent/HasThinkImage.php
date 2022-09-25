<?php

namespace SimpleImageManager\Eloquent;

use SimpleImageManager\Exceptions\SimpleImageManagerException;

trait HasThinkImage
{
    /**
     * Resolved images.
     *
     * @var array
     */
    protected array $thinkImages = [];

    /**
     * Map: field -> driver
     */
    abstract public function thinkImagesMap(): array;

    /**
     * Image manager.
     *
     * @param string $field
     *
     * @return ThinkImage|null
     * @throws SimpleImageManagerException
     */
    public function thinkImage(string $field): ThinkImage
    {
        if (!empty($this->thinkImages[ $field ])) {
            return $this->thinkImages[ $field ]->setValue($this->{$field});
        }
        $map = $this->thinkImagesMap();
        if (!array_key_exists($field, $map)) {
            throw new SimpleImageManagerException("Filed [{$field}]  not present in thinkImagesMap.");
        }

        return $this->thinkImages[ $field ] = ($map[ $field ] instanceof ThinkImage) ? $map[ $field ] : new ThinkImage($map[ $field ], $this->{$field});
    }
}
