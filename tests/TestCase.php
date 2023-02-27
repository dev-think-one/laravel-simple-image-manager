<?php

namespace SimpleImageManager\Tests;

use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\Database\MigrateProcessor;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        // fix resize big image test
        ini_set('memory_limit', '-1');

        parent::setUp();
        Storage::fake('avatars');
        Storage::fake('feature-images');
    }

    protected function getPackageProviders($app)
    {
        return [
            \SimpleImageManager\ServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();

        $migrator = new MigrateProcessor($this, [
            '--path'     => __DIR__.'/Fixtures/migrations',
            '--realpath' => true,
        ]);
        $migrator->up();
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
