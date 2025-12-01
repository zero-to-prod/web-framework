<?php

namespace Zerotoprod\WebFramework\Plugins;

use Zerotoprod\Phpdotenv\Phpdotenv;

/**
 * @link https://github.com/zero-to-prod/web-framework
 */
class EnvBinderImmutable
{
    /**
     * Parse environment string and bind to target environment immutably.
     *
     * @param  string  $env  The environment file content as a string
     * @param  array  &$target_env  Reference to environment array where variables will be bound
     *
     * @return array  The parsed environment variables
     *
     * @link https://github.com/zero-to-prod/web-framework
     */
    public static function parseFromString(string $env, array &$target_env): array
    {
        $parsed_env = Phpdotenv::parseFromString($env);

        foreach ($parsed_env as $key => $value) {
            if (isset($target_env[$key]) || getenv($key) !== false) {
                continue;
            }

            $target_env[$key] = $value;
            putenv("$key=$value");
        }

        return $parsed_env;
    }
}