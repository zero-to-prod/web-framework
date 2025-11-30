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
        return static function (string $env_content): array {
            return Phpdotenv::parseFromString($env_content);
        };
    }
}