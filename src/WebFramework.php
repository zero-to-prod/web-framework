<?php

namespace Zerotoprod\WebFramework;

use RuntimeException;
use Zerotoprod\WebFramework\Plugins\EnvBinderImmutable;
use Zerotoprod\WebFramework\Plugins\EnvParser;

/**
 * @link https://github.com/zero-to-prod/web-framework
 */
class WebFramework
{
    /**
     * The base path of the application.
     *
     * @var string
     */
    private $basePath;

    /**
     * The parser for the environment file.
     *
     * @var callable
     */
    private $envParser;

    /**
     * The path to the environment file.
     *
     * @var string
     */
    private $envPath;

    /**
     * The environment binder.
     *
     * @var callable
     */
    private $envBinder;

    /**
     * Reference to the environment array.
     *
     * @var array|null
     */
    private $envTarget;

    /**
     * Create a new WebFramework instance.
     *
     * @param  string  $basePath  The base path of the application.
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function __construct(string $basePath)
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
     * @param  array  &$envTarget  Reference to environment array where variables will be bound
     *
     * @return WebFramework  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function setEnvTarget(array &$envTarget): WebFramework
    {
        $this->envTarget = &$envTarget;

        return $this;
    }

    /**
     * Set the path to the environment file.
     *
     * @param  string  $envPath  The absolute or relative path to the .env file
     *
     * @return WebFramework  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function setEnvPath(string $envPath): WebFramework
    {
        $this->envPath = $envPath;

        return $this;
    }

    /**
     * Set the environment file parser.
     *
     * Provide a callable that accepts an environment file path and returns
     * an associative array of parsed environment variables.
     *
     * @param  callable(string $envPath): array  $callable  A callable that parses the environment file
     *
     * @return WebFramework  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function setEnvParser(callable $callable): WebFramework
    {
        $this->envParser = $callable;

        return $this;
    }

    /**
     * Set the environment variable binder.
     *
     * Provide a callable that accepts parsed environment variables and binds them
     * to the target environment array (e.g., $_ENV, getenv()).
     *
     * @param  callable(array $parsedEnv, array &$envTarget): void  $callable  A callable that binds environment variables
     *
     * @return WebFramework  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function setEnvBinder(callable $callable): WebFramework
    {
        $this->envBinder = $callable;

        return $this;
    }

    /**
     * Load default environment configuration.
     *
     * Sets default values for:
     * - envTarget: $_ENV global
     * - envPath: {basePath}/.env
     * - envParser: EnvParser plugin
     * - envBinder: EnvBinderImmutable plugin
     *
     * @return WebFramework  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function setEnvDefaults(): WebFramework
    {
        return $this
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->basePath . '/.env')
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle());
    }

    /**
     * Load and bind environment variables.
     *
     * Validates configuration, parses the environment file using the configured parser,
     * and binds variables to the target environment using the configured binder.
     *
     * @return WebFramework  Returns $this for method chaining
     *
     * @throws RuntimeException  If envParser, envBinder, or envPath is not configured
     * @throws RuntimeException  If the parser does not return an array
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function loadEnv(): WebFramework
    {
        if (!$this->envParser) {
            throw new RuntimeException('Environment parser not set.');
        }
        if (!$this->envBinder) {
            throw new RuntimeException('Environment binder not set.');
        }
        if (!$this->envPath) {
            throw new RuntimeException('Environment path not set.');
        }

        $parsedEnv = ($this->envParser)($this->envPath);

        if (!is_array($parsedEnv)) {
            throw new RuntimeException(
                sprintf(
                    'Environment parser must return an array, %s returned.',
                    gettype($parsedEnv)
                )
            );
        }

        ($this->envBinder)($parsedEnv, $this->envTarget);

        return $this;
    }
}