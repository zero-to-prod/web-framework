<?php

namespace Zerotoprod\WebFramework;

use Psr\Container\ContainerInterface;

/**
 * @link https://github.com/zero-to-prod/web-framework
 */
class WebFramework
{

    /**
     * @var ContainerInterface
     */
    private $Container;

    /**
     * The base path of the application.
     *
     * @var string
     */
    private $basePath;

    /**
     * Reference to the environment array.
     *
     * @var array|null
     */
    private $env;

    /**
     * Reference to the server array.
     *
     * @var array|null
     */
    private $server;

    /**
     * Create a new WebFramework instance.
     *
     * @param  string|null  $basePath  The base path of the application.
     *
     * @example new WebFramework(__DIR__);
     *
     * @link    https://github.com/zero-to-prod/web-framework
     */
    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath;
    }

    /**
     * Set the target environment array for binding.
     *
     * Provides an explicit way to bind environment variables to a custom array
     * instead of using the constructor parameter. Useful when you want to configure
     * the target environment after instantiation or when method chaining is preferred.
     *
     * @param  array  &$env  Reference to environment array where variables will be bound
     *
     * @return WebFramework  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function setEnv(array &$env): WebFramework
    {
        $this->env = &$env;

        return $this;
    }

    /**
     * Set the target server array for binding.
     *
     * @param  array  $server
     *
     * @return WebFramework  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function setServer(array &$server): WebFramework
    {
        $this->server = &$server;

        return $this;
    }

    public function setContainer(ContainerInterface $Container): WebFramework
    {
        $this->Container = $Container;

        return $this;
    }

    public function context(callable $callable): WebFramework
    {
        $callable($this);

        return $this;
    }

    public function container(): ContainerInterface
    {
        return $this->Container;
    }
}