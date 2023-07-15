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

        $file           = UploadedFile::fake()->image('avatar.png', 1700, 20);
        $fileBaseName   = Str::uuid();
        $originFilePath = $manager->upload($file, $fileBaseName);
        Storage::disk('avatars')->assertExists($originFilePath);

        $file2           = UploadedFile::fake()->image('avatar.png', 1700, 20);
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

    /** @test */
    public function upload_immutable_extension()
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

        $file           = UploadedFile::fake()->image('avatar.gif', 1700, 20);
        $fileBaseName   = Str::uuid();
        $originFilePath = $manager->upload($file, $fileBaseName);
        Storage::disk('avatars')->assertExists($originFilePath);
        $this->assertTrue(file_exists($manager->path($originFilePath)));
        $this->assertEquals($manager->path($originFilePath), $manager->path($originFilePath, 'small'));
        $this->assertTrue(file_exists($manager->path($originFilePath, 'small')));
        $this->assertEquals($manager->path($originFilePath), $manager->path($originFilePath, 'medium'));
        $this->assertTrue(file_exists($manager->path($originFilePath, 'medium')));

        $this->assertEquals($manager->url($originFilePath), $manager->url($originFilePath, 'small'));
        $this->assertEquals($manager->url($originFilePath), $manager->url($originFilePath, 'medium'));
    }

    /** @test */
    public function delete_also_empty_directory()
    {
        $manager = new ImageManager(array_merge(
            Config::get('simple-image-manager.drivers.avatars'),
            [
                'prefix'         => null,
                'truncateDir'    => true,
                'deletedFormats' => [
                    'mini',
                    'bigger',
                ],
            ]
        ));

        $file           = UploadedFile::fake()->image('avatar.png', 1700, 20);
        $directoryName  = 'fooBar';
        $fileBaseName   = $directoryName . DIRECTORY_SEPARATOR . Str::uuid();
        $originFilePath = $manager->upload($file, $fileBaseName);
        $storage        = Storage::disk('avatars');
        $storage->assertExists($directoryName);
        $storage->assertExists($originFilePath);
        $this->assertTrue(file_exists($manager->path($originFilePath)));
        $this->assertEquals($manager->path($originFilePath), $manager->path($originFilePath));
        $this->assertEquals($manager->url($originFilePath), $manager->url($originFilePath));

        $manager->delete($originFilePath);

        $storage->assertMissing($originFilePath);
        $storage->assertMissing($directoryName);
    }

    /** @test */
    public function directory_will_not_be_removed_if_exists_other_file()
    {
        $manager = new ImageManager(array_merge(
            Config::get('simple-image-manager.drivers.avatars'),
            [
                'prefix'         => null,
                'truncateDir'    => true,
                'deletedFormats' => [
                    'mini',
                    'bigger',
                ],
            ]
        ));

        $file           = UploadedFile::fake()->image('avatar.png', 1700, 20);
        $directoryName  = 'fooBar';
        $fileBaseName   = $directoryName . DIRECTORY_SEPARATOR . Str::uuid();
        $originFilePath = $manager->upload($file, $fileBaseName);
        $storage        = Storage::disk('avatars');
        $storage->assertExists($directoryName);
        $storage->assertExists($originFilePath);
        $this->assertTrue(file_exists($manager->path($originFilePath)));
        $this->assertEquals($manager->path($originFilePath), $manager->path($originFilePath));
        $this->assertEquals($manager->url($originFilePath), $manager->url($originFilePath));

        $this->assertCount(3, $storage->allFiles());
        $newFile = $directoryName . DIRECTORY_SEPARATOR . '.gitignore';
        $storage->put($newFile, '');
        $storage->assertExists($newFile);
        $this->assertCount(4, $storage->allFiles());

        $manager->delete($originFilePath);

        $storage->assertMissing($originFilePath);
        $storage->assertExists($directoryName);
        $this->assertCount(1, $storage->allFiles());
        $storage->assertExists($newFile);
    }

    /** @test */
    public function directory_will_not_be_removed_if_exists_other_file_in_directory()
    {
        $manager = new ImageManager(array_merge(
            Config::get('simple-image-manager.drivers.avatars'),
            [
                'prefix'         => null,
                'truncateDir'    => true,
                'deletedFormats' => [
                    'mini',
                    'bigger',
                ],
            ]
        ));

        $file           = UploadedFile::fake()->image('avatar.png', 1700, 20);
        $directoryName  = 'fooBar';
        $fileBaseName   = $directoryName . DIRECTORY_SEPARATOR . Str::uuid();
        $originFilePath = $manager->upload($file, $fileBaseName);
        $storage        = Storage::disk('avatars');
        $storage->assertExists($directoryName);
        $storage->assertExists($originFilePath);
        $this->assertTrue(file_exists($manager->path($originFilePath)));
        $this->assertEquals($manager->path($originFilePath), $manager->path($originFilePath));
        $this->assertEquals($manager->url($originFilePath), $manager->url($originFilePath));

        $this->assertCount(3, $storage->allFiles());
        $this->assertCount(1, $storage->allDirectories());
        $newFile = $directoryName . DIRECTORY_SEPARATOR . 'qux' . DIRECTORY_SEPARATOR . '.gitignore';
        $storage->put($newFile, '');
        $storage->assertExists($newFile);
        $this->assertCount(4, $storage->allFiles());
        $this->assertCount(2, $storage->allDirectories());

        $manager->delete($originFilePath);

        $storage->assertMissing($originFilePath);
        $storage->assertExists($directoryName);
        $this->assertCount(1, $storage->allFiles());
        $this->assertCount(2, $storage->allDirectories());
        $storage->assertExists($newFile);
        $storage->assertExists(dirname($newFile));
    }

    /** @test */
    public function directory_will_be_removed_if_exists_other_empty_directory()
    {
        $manager = new ImageManager(array_merge(
            Config::get('simple-image-manager.drivers.avatars'),
            [
                'prefix'         => null,
                'truncateDir'    => true,
                'deletedFormats' => [
                    'mini',
                    'bigger',
                ],
            ]
        ));

        $file           = UploadedFile::fake()->image('avatar.png', 1700, 20);
        $directoryName  = 'fooBar';
        $fileBaseName   = $directoryName . DIRECTORY_SEPARATOR . Str::uuid();
        $originFilePath = $manager->upload($file, $fileBaseName);
        $storage        = Storage::disk('avatars');
        $storage->assertExists($directoryName);
        $storage->assertExists($originFilePath);
        $this->assertTrue(file_exists($manager->path($originFilePath)));
        $this->assertEquals($manager->path($originFilePath), $manager->path($originFilePath));
        $this->assertEquals($manager->url($originFilePath), $manager->url($originFilePath));

        $this->assertCount(3, $storage->allFiles());
        $this->assertCount(1, $storage->allDirectories());
        $newDir = $directoryName . DIRECTORY_SEPARATOR . 'qux';
        $storage->makeDirectory($newDir);
        $storage->assertExists($newDir);
        $this->assertCount(3, $storage->allFiles());
        $this->assertCount(2, $storage->allDirectories());

        $manager->delete($originFilePath);

        $storage->assertMissing($originFilePath);
        $storage->assertMissing($directoryName);
        $this->assertCount(0, $storage->allFiles());
        $this->assertCount(0, $storage->allDirectories());
        $storage->assertMissing($newDir);
    }
}
