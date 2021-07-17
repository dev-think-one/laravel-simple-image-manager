<?php

namespace SimpleImageManager\Facades;

use Illuminate\Support\Facades\Facade;
use SimpleImageManager\Contracts\ImageManagerInterface;

/**
 * Class SimpleImageManager
 * @package SimpleImageManager\Facades
 *
 * @mixin ImageManagerInterface
 */
class SimpleImageManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'simple-image-manager';
    }
}
