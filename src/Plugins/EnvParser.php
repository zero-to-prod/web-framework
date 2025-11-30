<?php

namespace Zerotoprod\WebFramework\Plugins;

use Zerotoprod\Phpdotenv\Phpdotenv;

class EnvParser
{
    public static function handle(): callable
    {
        return static function (string $env_path): array {
            return Phpdotenv::parse(
                file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            );
        };
    }
}