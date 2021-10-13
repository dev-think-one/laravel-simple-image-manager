<?php

namespace SimpleImageManager\Tests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleImageManager\Eloquent\ThinkImage;
use SimpleImageManager\Exceptions\SimpleImageManagerException;
use SimpleImageManager\Tests\Fixtures\Models\Author;

class HasThinkImageTest extends TestCase
{

    /** @test */
    public function think_image_can_propagate_call()
    {
        /** @var Author $author */
        $author = Author::create();
        $this->assertInstanceOf(\Illuminate\Filesystem\FilesystemAdapter::class, $author->avatarImage()->storage());

        $this->expectException(\BadMethodCallException::class);
        $author->avatarImage()->randomMenthod();
    }

    /** @test */
    public function key_should_be_present()
    {
        /** @var Author $author */
        $author = Author::create();
        $this->assertInstanceOf(ThinkImage::class, $author->avatarImage());
        $this->assertInstanceOf(ThinkImage::class, $author->thinkImage('avatar'));

        $this->expectException(SimpleImageManagerException::class);
        $author->thinkImage('other_key');
    }

    /** @test */
    public function author_has_avatar()
    {
        /** @var Author $author */
        $author = Author::create();
        $this->assertNull($author->avatar);
        $this->assertInstanceOf(ThinkImage::class, $author->avatarImage());

        $this->assertEquals('my/path/there.svg', $author->avatarImage()->path());
        $this->assertEquals('http://localhost/img/default.svg', $author->avatarImage()->url());
        $this->assertEquals('other.svg', $author->avatarImage()->path(null, 'other.svg'));
        $this->assertEquals('other.png', $author->avatarImage()->url(null, 'other.png'));
        $this->assertEquals('other.svg', $author->avatarImage()->path('small', 'other.svg'));
        $this->assertEquals('other.png', $author->avatarImage()->url('small', 'other.png'));

        $this->assertStringContainsString('<img', $author->avatarImage()->img());
        $this->assertEmpty($author->avatarImage()->setDefaultUrl(null)->img());

        $author->avatar = 'my-file.jpg';
        $this->assertTrue(Str::endsWith($author->avatarImage()->path(), '/my-file.jpg'));
        $this->assertTrue(Str::endsWith($author->avatarImage()->url(), '/my-file.jpg'));
    }

    /** @test */
    public function author_can_manipulate_with_avatar()
    {
        /** @var Author $author */
        $author = Author::create();
        $this->assertNull($author->avatar);
        $this->assertFalse($author->avatarImage()->delete());
        $this->assertInstanceOf(ThinkImage::class, $author->avatarImage());

        $file         = UploadedFile::fake()->image('avatar.jpg', 1700, 20);
        $fileBaseName = Str::uuid();

        $originFilePath = $author->avatarImage()->upload($file, $fileBaseName);

        $this->assertNull($author->avatar);
        $this->assertNotEmpty($originFilePath);
        Storage::disk('avatars')->assertExists($originFilePath);
        $this->assertTrue(file_exists($author->avatarImage()->storage()->path($originFilePath)));

        $author->avatar = $originFilePath;

        $this->assertEquals($author->avatarImage()->storage()->path($originFilePath), $author->avatarImage()->path());
        $this->assertTrue(file_exists($author->avatarImage()->path()));
        $this->assertTrue(file_exists($author->avatarImage()->path('small')));
        $this->assertTrue(file_exists($author->avatarImage()->path('medium')));

        $author->save();
        $author->refresh();

        $this->assertTrue(file_exists($author->avatarImage()->path()));
        $this->assertTrue(file_exists($author->avatarImage()->path('small')));
        $this->assertTrue(file_exists($author->avatarImage()->path('medium')));
        $this->assertTrue(Str::endsWith($author->avatarImage()->path('small'), "{$fileBaseName}-small.jpg"));
        $this->assertTrue(Str::endsWith($author->avatarImage()->url('medium'), "{$fileBaseName}-medium.jpg"));

        $author->avatarImage()->delete();
        $this->assertFalse(file_exists($author->avatarImage()->path()));
        $this->assertFalse(file_exists($author->avatarImage()->path('small')));
        $this->assertFalse(file_exists($author->avatarImage()->path('medium')));
    }

    /** @test */
    public function author_can_upload_iamge_without_name()
    {
        /** @var Author $author */
        $author = Author::create();

        $file         = UploadedFile::fake()->image('avatar.jpg', 1700, 20);

        $originFilePath = $author->avatarImage()->upload($file);

        $this->assertNull($author->avatar);
        $this->assertNotEmpty($originFilePath);
        Storage::disk('avatars')->assertExists($originFilePath);
        $this->assertTrue(file_exists($author->avatarImage()->storage()->path($originFilePath)));
    }
}
