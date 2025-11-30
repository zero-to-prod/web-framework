<?php

namespace Zerotoprod\WebFramework\Plugins;

/**
 * @link https://github.com/zero-to-prod/web-framework
 */
class EnvBinderImmutable
{
    /**
     * @link https://github.com/zero-to-prod/web-framework
     */
    public static function handle(): callable
    {
        return static function (array $parsed_env, array &$target_env) {
            foreach ($parsed_env as $key => $value) {
                if (isset($target_env[$key]) || getenv($key) !== false) {
                    continue;
                }

                $target_env[$key] = $value;
                putenv("$key=$value");
            }
        };
    }
}