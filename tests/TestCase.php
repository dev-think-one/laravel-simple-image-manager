<?php

namespace SimpleImageManager\Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        array_map('unlink', glob(__DIR__ . '/../vendor/orchestra/testbench-core/laravel/database/migrations/*.php'));
        // $this->artisan( 'vendor:publish', [ '--tag' => 'migrations', '--force' => true ] );
        array_map(function ($f) {
            File::copy($f, __DIR__ . '/../vendor/orchestra/testbench-core/laravel/database/migrations/' . basename($f));
        }, glob(__DIR__ . '/Fixtures/migrations/*.php'));


        $this->artisan('migrate', [ '--database' => 'testbench' ]);

        Storage::fake('avatars');
        Storage::fake('feature-images');
    }

    protected function getPackageProviders($app)
    {
        return [
            \SimpleImageManager\ServiceProvider::class,
        ];
    }

    public function defineEnvironment($app)
    {
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set filesystem
        $app['config']->set('filesystems.disks', array_merge(
            $app['config']->get('filesystems.disks'),
            [
                'avatars'        => [
                    'driver'     => 'local',
                    'root'       => storage_path('app/public/authors-avatars'),
                    'url'        => 'http://localhost/storage/authors-avatars',
                    'visibility' => 'public',
                ],
                'feature-images' => [
                    'driver'     => 'local',
                    'root'       => storage_path('app/public/feature-images'),
                    'url'        => 'http://localhost/storage/feature-images',
                    'visibility' => 'public',
                ],
            ]
        ));

        // $app['config']->set('simple-image-manager.some_key', 'some_value');
    }
}
