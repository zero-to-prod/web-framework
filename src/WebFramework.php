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
     * The environment binder.
     *
     * @var callable
     */
    private $envBinder;

    /**
     * The environment file content.
     *
     * @var string|null
     */
    private $envContent;

    /**
     * Reference to the environment array.
     *
     * @var array|null
     */
    private $envTarget;

    /**
     * Reference to the server array.
     *
     * @var array|null
     */
    private $serverTarget;

    /**
     * Create a new WebFramework instance.
     *
     * @example new WebFramework(cwd);
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
     * Set the target server array for binding.
     *
     * @param  array  $serverTarget
     *
     * @return WebFramework  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function setServerTarget(array &$serverTarget): WebFramework
    {
        $this->serverTarget = &$serverTarget;

        return $this;
    }

    /**
     * @param  callable(array $envTarget, array $serverTarget): array  $definition
     *
     * @return WebFramework  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function handleRoutes(callable $definition): WebFramework
    {
        $definition($this->envTarget, $this->serverTarget);

        return $this;
    }

    /**
     * Set the environment file parser.
     *
     * Provide a callable that accepts environment file content as a string and returns
     * an associative array of parsed environment variables.
     *
     * @param  callable(string $envContent): array  $callable  A callable that parses the environment file content
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
     * Set the environment file content.
     *
     * Provide the raw environment file content as a string that will be parsed
     * and bound when bindEnv() is called.
     *
     * @param  string  $envContent  The environment file content as a string
     *
     * @return WebFramework  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function setEnvContent(string $envContent): WebFramework
    {
        $this->envContent = $envContent;

        return $this;
    }

    /**
     * Load default environment configuration.
     *
     * Sets default values for:
     * - envTarget: $_ENV global
     * - envParser: EnvParser plugin
     * - envBinder: EnvBinderImmutable plugin
     *
     * @return WebFramework  Returns $this for method chaining
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function setEnvDefaults(array &$targetEnv, string $envContent): WebFramework
    {
        return $this
            ->setEnvTarget($targetEnv)
            ->setEnvContent($envContent)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle());
    }

    /**
     * Bind environment variables to the target environment.
     *
     * Parses the stored environment content string using the configured parser,
     * and binds variables to the target environment using the configured binder.
     *
     * @return WebFramework  Returns $this for method chaining
     *
     * @throws RuntimeException  If envParser, envBinder, or envContent is not configured
     * @throws RuntimeException  If the parser does not return an array
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function bindEnv(): WebFramework
    {
        if (!$this->envParser) {
            throw new RuntimeException('Environment parser not set.');
        }
        if (!$this->envBinder) {
            throw new RuntimeException('Environment binder not set.');
        }
        if ($this->envContent === null) {
            throw new RuntimeException('Environment content not set.');
        }

        $parsedEnv = ($this->envParser)($this->envContent);

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