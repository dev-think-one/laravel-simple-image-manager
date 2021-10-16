<?php

namespace SimpleImageManager;

use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use SimpleImageManager\Contracts\ImageManagerInterface;
use SimpleImageManager\Managers\ImageManager;

class SimpleImageManager
{

    /**
     * Configuration filtration callback.
     *
     * @var \Closure|null
     */
    protected static ?\Closure $filterConfigCallback = null;

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

    /**
     * Add additional filter for config data. Can be user in app service provider.
     *
     * @param \Closure|null $callback
     */
    public static function filterConfigUsing(?\Closure $callback): void
    {
        static::$filterConfigCallback = $callback;
    }

    /**
     * Get default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->app->get('config')['simple-image-manager.default.driver'];
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    protected function getConfig(string $name): mixed
    {
        $configData = $this->app->get('config')["simple-image-manager.drivers.{$name}"] ?? null;

        return $this->filterConfig($name, $configData);
    }

    /**
     * @param string $driver
     * @param mixed $config
     *
     * @return mixed
     */
    protected function filterConfig(string $driver, mixed $config): mixed
    {
        if (is_callable(static::$filterConfigCallback)) {
            return call_user_func(static::$filterConfigCallback, $driver, $config);
        }

        return $config;
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
