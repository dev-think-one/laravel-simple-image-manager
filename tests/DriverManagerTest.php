<?php

namespace SimpleImageManager\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SimpleImageManager\Facades\SimpleImageManager;
use SimpleImageManager\Managers\ImageManager;
use SimpleImageManager\Tests\Fixtures\Managers\CustomImageManager;
use SimpleImageManager\Tests\Fixtures\Managers\WrongCustomImageManager;

class DriverManagerTest extends TestCase
{
    /** @test */
    public function get_default_driver()
    {
        $manager = SimpleImageManager::driver();
        $this->assertInstanceOf(ImageManager::class, $manager);
    }

    /** @test */
    public function exception_if_wrong_driver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Simple image manager driver [my-example-driver] is not defined.');
        SimpleImageManager::driver('my-example-driver');
    }

    /** @test */
    public function custom_manager()
    {
        Config::set('simple-image-manager.drivers.avatars', array_merge(
            Config::get('simple-image-manager.drivers.avatars'),
            [
                'manager' => CustomImageManager::class,
            ]
        ));
        $manager = SimpleImageManager::driver('avatars');

        $this->assertInstanceOf(CustomImageManager::class, $manager);
    }

    /** @test */
    public function wrong_custom_manager()
    {
        Config::set('simple-image-manager.drivers.avatars', array_merge(
            Config::get('simple-image-manager.drivers.avatars'),
            [
                'manager' => WrongCustomImageManager::class,
            ]
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver [avatars] has wong manager.');

        SimpleImageManager::driver('avatars');
    }

    /** @test */
    public function propagate_call()
    {
        $this->assertTrue(Str::endsWith(SimpleImageManager::url('some-file.png'), '/some-file.png'));
    }
}
