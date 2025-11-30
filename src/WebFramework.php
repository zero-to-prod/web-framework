<?php

namespace Zerotoprod\WebFramework;

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
     * @var array
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
     * @var callable|null
     */
    private $envBinder;

    /**
     * Reference to the environment array.
     *
     * @var array
     */
    private $env;

    /**
     * Create a new WebFramework instance.
     *
     * @param              $basePath string The base path of the application.
     * @param  array|null  $env      array Optional reference to environment array (defaults to $_ENV)
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function __construct(string $basePath, ?array &$env = null)
    {
        $this->basePath = $basePath;
        $this->env = &$env;
    }

    /**
     * Set the path to the environment file.
     *
     * @param  string  $envPath
     *
     * @return $this
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function envPathSet(string $envPath): WebFramework
    {
        $this->envPath = $envPath;

        return $this;
    }

    /**
     * Set the environment variables.
     *
     * @param  ?callable(string $env_path): array  $callable
     *
     * @return $this
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function envParserSet(?callable $callable = null): WebFramework
    {
        $this->envParser = $callable;

        return $this;
    }

    /**
     * Bind the environment variables to the global scope immutably.
     *
     * @param  ?callable(array $parsed_env, array &$target_env): void  $callable
     *
     * @return $this
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function envBinderSet(?callable $callable = null): WebFramework
    {
        $this->envBinder = $callable;

        return $this;
    }

    /**
     * @link https://github.com/zero-to-prod/web-framework
     */
    public function run(): self
    {
        if ($this->envPath && $this->envParser && $this->envBinder) {
            ($this->envBinder)(($this->envParser)($this->envPath), $this->env);
        }

        return $this;
    }
}