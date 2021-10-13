<?php

namespace SimpleImageManager\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleImageManager\Facades\SimpleImageManager;
use SimpleImageManager\Managers\ImageManager;

class ImageManagerTest extends TestCase
{

    /** @test */
    public function if_empty_path_then_return_null()
    {
        $manager = SimpleImageManager::driver('avatars');

        $this->assertNull($manager->url(''));
        $this->assertNull($manager->path(''));
        $this->assertFalse($manager->delete(''));
    }

    /** @test */
    public function constructor_should_have_disk()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Driver configuration has not key 'disk'");
        new ImageManager([]);
    }

    /** @test */
    public function upload_image_with_replacing()
    {
        $manager = new ImageManager(array_merge(
            Config::get('simple-image-manager.drivers.avatars'),
            [
                'prefix'         => 'my-pref',
                'deletedFormats' => [
                    'mini',
                    'bigger',
                ],
            ]
        ));

        $file           = UploadedFile::fake()->image('avatar.png', 40, 20);
        $fileBaseName   = Str::uuid();
        $originFilePath = $manager->upload($file, $fileBaseName);
        Storage::disk('avatars')->assertExists($originFilePath);

        $file2           = UploadedFile::fake()->image('avatar.png', 40, 20);
        $file2BaseName   = Str::uuid();
        $originFile2Path = $manager->upload($file2, $file2BaseName, $originFilePath);
        Storage::disk('avatars')->assertExists($originFile2Path);
        Storage::disk('avatars')->assertMissing($originFilePath);
        $this->assertTrue(file_exists($manager->path($originFile2Path)));
        $this->assertTrue(file_exists($manager->path($originFile2Path, 'small')));
        $this->assertTrue(file_exists($manager->path($originFile2Path, 'medium')));

        $this->assertFalse($manager->deleteSingle(''));
        $manager->deleteSingle($originFile2Path, 'small');
        $this->assertTrue(file_exists($manager->path($originFile2Path)));
        $this->assertFalse(file_exists($manager->path($originFile2Path, 'small')));
        $this->assertTrue(file_exists($manager->path($originFile2Path, 'medium')));

        $manager->deleteSingle($originFile2Path);
        $this->assertFalse(file_exists($manager->path($originFile2Path)));
        $this->assertFalse(file_exists($manager->path($originFile2Path, 'small')));
        $this->assertTrue(file_exists($manager->path($originFile2Path, 'medium')));

        $manager->delete($originFile2Path);
        $this->assertFalse(file_exists($manager->path($originFile2Path)));
        $this->assertFalse(file_exists($manager->path($originFile2Path, 'small')));
        $this->assertFalse(file_exists($manager->path($originFile2Path, 'medium')));
    }
}
