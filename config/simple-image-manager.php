<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Driver Name
    |--------------------------------------------------------------------------
    |
    | In project you can have multiple configurations. So there yous can specify
    | default driver configuration.
    |
    */

    'default' => [
        'driver' => 'avatars',
    ],

    /*
    |--------------------------------------------------------------------------
    | Drivers list
    |--------------------------------------------------------------------------
    |
    | Configurations list.
    |
    | Driver supported options:
    | - *disk* - laravel filesystem disk (NOTE: currently package support only "local" disks)
    | - *immutableExtensions* - Extensions what should not be changeable by library
    | - *truncateDir* - Clear directory what contained images after deleting directory. Preferable to set it `true` only
    |    in case if you will have small amount of images, as system calculate allFiles before deletion what can cause memory and timeout exception.
    | - *prefix* - file name prefix (you can specify directory as prefix)
    | - *original* - original  file configuration. You can set as:
    |   - "false" - do not save original file
    |   - "true" - save original file with default configs
    |   - array - configuration array:
    |       - *methods* - list of \Spatie\Image\Image methods and parameters (https://spatie.be/docs/image).
    |          You can't change image extension because currently package support only original loaded extension.
    | - *deletedFormats* - If you deleted some format than on next reupload this format will not be deleted. That why you need specify this formats.
    | - *formats* - additional file formats. Key is suffix, value is options list. Each format supports same options as "original".
    | - *manager* - You can specify you own custom manager. It should implements SimpleImageManager\Contracts\ImageManagerInterface
    |
    */

    'drivers' => [
        'avatars' => [
            'disk' => 'avatars',
            # 'prefix'             => 'some-folder/',
            'truncateDir'         => false,
            'immutableExtensions' => ['.svg', '.gif'],
            'original'            => [
                'methods' => [
                    'fit'      => [\Spatie\Image\Manipulations::FIT_CROP, 500, 500],
                    'optimize' => [],
                ],
                'srcset' => '500w',
            ],
            'deletedFormats' => [],
            'formats'        => [
                'medium' => [
                    'methods' => [
                        'fit'      => [\Spatie\Image\Manipulations::FIT_CROP, 250, 250],
                        'optimize' => [],
                    ],
                    'srcset' => '250w',
                ],
                'small' => [
                    'methods' => [
                        'fit'      => [\Spatie\Image\Manipulations::FIT_CROP, 100, 100],
                        'optimize' => [],
                    ],
                    'srcset' => '100w',
                ],
            ],
        ],
        'feature-images' => [
            'disk'                => 'feature-images',
            'truncateDir'         => false,
            'immutableExtensions' => ['.svg', '.gif'],
            'original'            => [
                'methods' => [
                    'fit'      => [\Spatie\Image\Manipulations::FIT_CROP, 2800, 1800],
                    'optimize' => [],
                ],
                'srcset' => '2800w',
            ],
            'deletedFormats' => [],
            'formats'        => [
                'thumb' => [
                    'methods' => [
                        'fit'      => [\Spatie\Image\Manipulations::FIT_CROP, 450, 300],
                        'optimize' => [],
                    ],
                    'srcset' => '450w',
                ],
            ],
        ],
    ],
];
