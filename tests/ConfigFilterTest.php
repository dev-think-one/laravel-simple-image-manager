<?php

namespace SimpleImageManager\Tests;

use SimpleImageManager\Facades\SimpleImageManager;
use SimpleImageManager\Managers\ImageManager;

class ConfigFilterTest extends TestCase
{


    /** @test */
    public function constructor_should_have_disk()
    {
        \SimpleImageManager\SimpleImageManager::filterConfigUsing(function ($name, $config) {
            $this->assertEquals('avatars', $name);

            $config['disk'] = 'my_test_new_disk';

            return $config;
        });
        /** @var ImageManager $imageManager */
        $imageManager = SimpleImageManager::driver('avatars');

        $this->assertEquals('my_test_new_disk', $imageManager->disk);
        \SimpleImageManager\SimpleImageManager::filterConfigUsing(null);

        $imageManager2 = SimpleImageManager::driver('avatars');

        $this->assertEquals($imageManager, $imageManager2);
    }
}
