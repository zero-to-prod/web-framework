<?php

namespace Zerotoprod\WebFramework\Plugins;

use Zerotoprod\Phpdotenv\Phpdotenv;

/**
 * @link https://github.com/zero-to-prod/web-framework
 */
class EnvParser
{
    /**
     * @link https://github.com/zero-to-prod/web-framework
     */
    public static function handle(): callable
    {
        return static function (string $env_path): array {
            return Phpdotenv::parse(
                file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            );
        };
    }
}