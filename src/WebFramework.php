<?php

namespace Zerotoprod\WebFramework;

use Closure;
use Zerotoprod\Phpdotenv\Phpdotenv;

class WebFramework
{
    /**
     * The base path of the application.
     *
     * @var string
     */
    private $base_path;

    /**
     * The environment variables.
     *
     * @var array
     */
    private $env;

    /**
     * The path to the environment file.
     *
     * @var string
     */
    private $env_path;

    /**
     * Create a new WebFramework instance.
     *
     * @param $basePath string The base path of the application.
     *
     * @return void
     */
    public function __construct(string $basePath)
    {
        $this->base_path = $basePath;
    }

    public function setEnvPath(string $env_path): WebFramework
    {
        $this->env_path = $env_path;

        return $this;
    }

    /**
     * @param  ?callable(string $env_path): array  $callable
     *
     * @return $this
     */
    public function setEnv(?callable $callable = null): WebFramework
    {
        if (!$callable instanceof Closure) {
            $callable = static function (string $env_path): array {
                return Phpdotenv::parse(
                    file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
                );
            };
        }

        $this->env = $callable($this->env_path);

        return $this;
    }

    /**
     * Bind the environment variables to the global scope immutably.
     *
     * @param  ?callable(array $env): void  $callable
     *
     * @return $this
     */
    public function bindEnvsToGlobalsImmutable(?callable $callable = null): WebFramework
    {
        if (!$callable instanceof Closure) {
            $callable = static function (array $env) {
                foreach ($env as $key => $value) {
                    if (isset($_ENV[$key]) || getenv($key) !== false) {
                        continue;
                    }

                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            };
        }
        $callable($this->env);

        return $this;
    }
}