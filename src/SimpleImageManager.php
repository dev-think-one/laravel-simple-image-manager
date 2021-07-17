<?php

namespace SimpleImageManager;

use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use SimpleImageManager\Contracts\ImageManagerInterface;
use SimpleImageManager\Managers\ImageManager;

class SimpleImageManager
{

    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The array of resolved services.
     */
    protected array $services = [];

    /**
     * Create a new instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getDefaultDriver()
    {
        return $this->app->get('config')['simple-image-manager.default.driver'];
    }

    protected function getConfig(string $name)
    {
        return $this->app->get('config')["simple-image-manager.drivers.{$name}"] ?? null;
    }

    /**
     * Get a driver instance.
     *
     * @param string|null $driver
     *
     * @return ImageManagerInterface
     */
    public function driver(?string $driver = null): ImageManagerInterface
    {
        return $this->get($driver ?? $this->getDefaultDriver());
    }

    /**
     * Create a driver instance.
     *
     * @param string $name
     *
     * @return ImageManagerInterface
     */
    protected function get(string $name): ImageManagerInterface
    {
        return $this->services[ $name ] ?? ($this->services[ $name ] = $this->resolve($name));
    }

    /**
     * @param string $name
     *
     * @return ImageManagerInterface
     */
    protected function resolve(string $name): ImageManagerInterface
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Simple image manager driver [{$name}] is not defined.");
        }

        if (!empty($config['manager'])) {
            $manager = $config['manager'];
            if (is_string($manager) &&
                class_exists($manager) &&
                is_subclass_of($manager, ImageManagerInterface::class)) {
                return new $manager($config);
            }

            throw new InvalidArgumentException("Driver [{$name}] has wong manager.");
        }

        return new ImageManager($config);
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}
