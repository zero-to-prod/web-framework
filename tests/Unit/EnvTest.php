<?php

namespace Tests\Unit;

use Tests\TestCase;
use Zerotoprod\WebFramework\Plugins\EnvBinderImmutable;
use Zerotoprod\WebFramework\Plugins\EnvParser;
use Zerotoprod\WebFramework\WebFramework;

class EnvTest extends TestCase
{
    private $temp_env_file;
    private $base_path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base_path = sys_get_temp_dir();
        $this->temp_env_file = tempnam(sys_get_temp_dir(), 'env_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->temp_env_file)) {
            unlink($this->temp_env_file);
        }

        // Clean up environment variables set during tests
        $test_keys = [
            'TEST_VAR',
            'TEST_VAR_1',
            'TEST_VAR_2',
            'TEST_VAR_3',
            'APP_NAME',
            'APP_ENV',
            'DB_HOST',
            'DB_PORT',
            'IMMUTABLE_VAR',
            'EXISTING_VAR',
            'NEW_VAR',
            'CUSTOM_VAR_1',
            'CUSTOM_VAR_2',
            'DEFAULT_VAR_1',
            'DEFAULT_VAR_2',
            'CUSTOM_PATH_VAR',
            'CUSTOM_PARSER_VAR',
            'BINDER_TEST_VAR',
            'CHAIN_VAR',
            'LOCAL_VAR',
            'ENV_TYPE',
            'LATE_VAR',
            'SWITCH_VAR_FIRST',
            'SWITCH_VAR_SECOND'
        ];

        foreach ($test_keys as $key) {
            unset($_ENV[$key]);
            putenv($key);
        }

        parent::tearDown();
    }

    /** @test */
    public function env_path_set_returns_instance_for_chaining(): void
    {
        $web_framework = new WebFramework($this->base_path);

        $result = $web_framework->setEnvPath($this->temp_env_file);

        $this->assertSame($web_framework, $result);
    }

    /** @test */
    public function env_parser_set_returns_instance_for_chaining(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR_1=value1\nTEST_VAR_2=value2");

        $web_framework = new WebFramework($this->base_path);
        $result = $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle());

        $this->assertSame($web_framework, $result);
    }

    /** @test */
    public function run_sets_env_variables_to_globals(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR_1=test_value_1\nTEST_VAR_2=test_value_2");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals('test_value_1', $_ENV['TEST_VAR_1']);
        $this->assertEquals('test_value_2', $_ENV['TEST_VAR_2']);
        $this->assertEquals('test_value_1', getenv('TEST_VAR_1'));
        $this->assertEquals('test_value_2', getenv('TEST_VAR_2'));
    }

    /** @test */
    public function run_with_immutable_binder_does_not_overwrite_existing_env_variables(): void
    {
        $_ENV['EXISTING_VAR'] = 'original_value';
        putenv('EXISTING_VAR=original_value');

        file_put_contents($this->temp_env_file, "EXISTING_VAR=new_value\nNEW_VAR=new_value");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals('original_value', getenv('EXISTING_VAR'));
        $this->assertEquals('new_value', $_ENV['NEW_VAR']);
        $this->assertEquals('new_value', getenv('NEW_VAR'));
    }

    /** @test */
    public function run_with_immutable_binder_does_not_overwrite_when_var_exists_in_env_array_only(): void
    {
        $_ENV['IMMUTABLE_VAR'] = 'env_array_value';

        file_put_contents($this->temp_env_file, "IMMUTABLE_VAR=file_value");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals('env_array_value', $_ENV['IMMUTABLE_VAR']);
    }

    /** @test */
    public function run_with_immutable_binder_does_not_overwrite_when_var_exists_in_getenv_only(): void
    {
        putenv('IMMUTABLE_VAR=getenv_value');

        file_put_contents($this->temp_env_file, "IMMUTABLE_VAR=file_value");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals('getenv_value', getenv('IMMUTABLE_VAR'));
    }

    /** @test */
    public function run_returns_instance_for_chaining(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR=value");

        $web_framework = new WebFramework($this->base_path);
        $result = $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertSame($web_framework, $result);
    }

    /** @test */
    public function env_binder_set_accepts_custom_callable(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR=value");

        $custom_bound_vars = [];

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(function ($parsed_env, &$target_env) use (&$custom_bound_vars) {
                $custom_bound_vars = $parsed_env;
            })
            ->loadEnv();

        $this->assertArrayHasKey('TEST_VAR', $custom_bound_vars);
        $this->assertEquals('value', $custom_bound_vars['TEST_VAR']);
    }

    /** @test */
    public function method_chaining_works_for_full_workflow(): void
    {
        file_put_contents($this->temp_env_file, "APP_NAME=TestApp\nAPP_ENV=testing");

        $web_framework = (new WebFramework($this->base_path))
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals('TestApp', $_ENV['APP_NAME']);
        $this->assertEquals('testing', $_ENV['APP_ENV']);
    }

    /** @test */
    public function run_handles_env_file_with_empty_lines(): void
    {
        $content = "TEST_VAR_1=value1\n\nTEST_VAR_2=value2\n\n\nTEST_VAR_3=value3";
        file_put_contents($this->temp_env_file, $content);

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals('value1', $_ENV['TEST_VAR_1']);
        $this->assertEquals('value2', $_ENV['TEST_VAR_2']);
        $this->assertEquals('value3', $_ENV['TEST_VAR_3']);
    }

    /** @test */
    public function run_handles_env_file_with_special_characters(): void
    {
        $content = "DB_HOST=localhost\nDB_PORT=3306\nAPP_NAME=\"My App Name\"";
        file_put_contents($this->temp_env_file, $content);

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals('localhost', $_ENV['DB_HOST']);
        $this->assertEquals('3306', $_ENV['DB_PORT']);
    }

    /** @test */
    public function custom_parser_receives_env_path(): void
    {
        $received_path = null;

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(function ($env_path) use (&$received_path) {
                $received_path = $env_path;

                return [];
            })
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals($this->temp_env_file, $received_path);
    }

    /** @test */
    public function custom_binder_receives_parsed_env_and_target_env(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR_1=value1\nTEST_VAR_2=value2");

        $received_parsed_env = null;
        $received_target_env = null;

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(function ($parsed_env, &$target_env) use (&$received_parsed_env, &$received_target_env) {
                $received_parsed_env = $parsed_env;
                $received_target_env = $target_env;
            })
            ->loadEnv();

        $this->assertIsArray($received_parsed_env);
        $this->assertArrayHasKey('TEST_VAR_1', $received_parsed_env);
        $this->assertArrayHasKey('TEST_VAR_2', $received_parsed_env);
        $this->assertIsArray($received_target_env);
    }

    /** @test */
    public function can_call_run_multiple_times(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR=first_value");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals('first_value', $_ENV['TEST_VAR']);

        // Unset to allow the immutable binder to set it again
        unset($_ENV['TEST_VAR']);
        putenv('TEST_VAR');

        file_put_contents($this->temp_env_file, "TEST_VAR=second_value");

        $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals('second_value', $_ENV['TEST_VAR']);
    }

    /** @test */
    public function can_inject_custom_env_array(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR_1=value1\nTEST_VAR_2=value2");

        $custom_env = [];

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($custom_env)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals('value1', $custom_env['TEST_VAR_1']);
        $this->assertEquals('value2', $custom_env['TEST_VAR_2']);
        $this->assertArrayNotHasKey('TEST_VAR_1', $_ENV);
        $this->assertArrayNotHasKey('TEST_VAR_2', $_ENV);
    }

    /** @test */
    public function run_throws_exception_when_env_parser_not_set(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment parser not set.');

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();
    }

    /** @test */
    public function run_throws_exception_when_env_binder_not_set(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment binder not set.');

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->loadEnv();
    }

    /** @test */
    public function run_throws_exception_when_env_path_not_set(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment path not set.');

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();
    }

    /** @test */
    public function run_throws_exception_when_parser_returns_null(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment parser must return an array, NULL returned.');

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(function ($env_path) {
                return null;
            })
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();
    }

    /** @test */
    public function run_throws_exception_when_parser_returns_string(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment parser must return an array, string returned.');

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(function ($env_path) {
                return 'invalid_return_value';
            })
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();
    }

    /** @test */
    public function run_throws_exception_when_parser_returns_integer(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment parser must return an array, integer returned.');

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(function ($env_path) {
                return 123;
            })
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();
    }

    /** @test */
    public function run_throws_exception_when_parser_returns_boolean(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment parser must return an array, boolean returned.');

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(function ($env_path) {
                return false;
            })
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();
    }

    /** @test */
    public function run_throws_exception_when_parser_returns_object(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Environment parser must return an array, object returned.');

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(function ($env_path) {
                return new \stdClass();
            })
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();
    }

    /** @test */
    public function load_env_defaults_returns_instance_for_chaining(): void
    {
        $web_framework = new WebFramework($this->base_path);

        $result = $web_framework->setEnvDefaults();

        $this->assertSame($web_framework, $result);
    }

    /** @test */
    public function load_env_defaults_allows_simple_usage(): void
    {
        $env_file = $this->base_path . '/.env';
        file_put_contents($env_file, "DEFAULT_VAR_1=default_value_1\nDEFAULT_VAR_2=default_value_2");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvDefaults()
            ->loadEnv();

        $this->assertEquals('default_value_1', $_ENV['DEFAULT_VAR_1']);
        $this->assertEquals('default_value_2', $_ENV['DEFAULT_VAR_2']);
        $this->assertEquals('default_value_1', getenv('DEFAULT_VAR_1'));
        $this->assertEquals('default_value_2', getenv('DEFAULT_VAR_2'));

        unlink($env_file);
    }

    /** @test */
    public function load_env_defaults_can_be_overridden_with_custom_path(): void
    {
        $custom_env_file = tempnam(sys_get_temp_dir(), 'custom_env_');
        file_put_contents($custom_env_file, "CUSTOM_PATH_VAR=custom_value");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvDefaults()
            ->setEnvPath($custom_env_file)
            ->loadEnv();

        $this->assertEquals('custom_value', $_ENV['CUSTOM_PATH_VAR']);
        $this->assertEquals('custom_value', getenv('CUSTOM_PATH_VAR'));

        unlink($custom_env_file);
    }

    /** @test */
    public function load_env_defaults_can_be_overridden_with_custom_parser(): void
    {
        file_put_contents($this->temp_env_file, "IGNORED=ignored_value");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvDefaults()
            ->setEnvParser(function ($env_path) {
                return ['CUSTOM_PARSER_VAR' => 'custom_parser_value'];
            })
            ->loadEnv();

        $this->assertEquals('custom_parser_value', $_ENV['CUSTOM_PARSER_VAR']);
        $this->assertArrayNotHasKey('IGNORED', $_ENV);
    }

    /** @test */
    public function load_env_defaults_can_be_overridden_with_custom_binder(): void
    {
        $env_file = $this->base_path . '/.env';
        file_put_contents($env_file, "BINDER_TEST_VAR=test_value");

        $custom_bound_vars = [];

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvDefaults()
            ->setEnvBinder(function ($parsed_env, &$target_env) use (&$custom_bound_vars) {
                $custom_bound_vars = $parsed_env;
            })
            ->loadEnv();

        $this->assertArrayHasKey('BINDER_TEST_VAR', $custom_bound_vars);
        $this->assertEquals('test_value', $custom_bound_vars['BINDER_TEST_VAR']);

        unlink($env_file);
    }

    /** @test */
    public function load_env_defaults_works_in_method_chain(): void
    {
        $env_file = $this->base_path . '/.env';
        file_put_contents($env_file, "CHAIN_VAR=chain_value");

        $web_framework = (new WebFramework($this->base_path))
            ->setEnvDefaults()
            ->loadEnv();

        $this->assertEquals('chain_value', $_ENV['CHAIN_VAR']);

        unlink($env_file);
    }

    /** @test */
    public function load_env_defaults_path_can_be_overridden_with_custom_filename(): void
    {
        $env_file = $this->base_path . '/.env.local';
        file_put_contents($env_file, "LOCAL_VAR=local_value");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvDefaults()
            ->setEnvPath($this->base_path . '/.env.local')
            ->loadEnv();

        $this->assertEquals('local_value', $_ENV['LOCAL_VAR']);

        unlink($env_file);
    }

    /** @test */
    public function load_env_defaults_supports_different_environments(): void
    {
        $production_file = $this->base_path . '/.env.production';
        file_put_contents($production_file, "APP_ENV=production\nDB_HOST=prod-db.example.com");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvDefaults()
            ->setEnvPath($this->base_path . '/.env.production')
            ->loadEnv();

        $this->assertEquals('production', $_ENV['APP_ENV']);
        $this->assertEquals('prod-db.example.com', $_ENV['DB_HOST']);

        unlink($production_file);
    }

    /** @test */
    public function load_env_defaults_with_custom_filename_can_be_overridden(): void
    {
        $env_local = $this->base_path . '/.env.local';
        $env_testing = $this->base_path . '/.env.testing';

        file_put_contents($env_local, "ENV_TYPE=local");
        file_put_contents($env_testing, "ENV_TYPE=testing");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvDefaults()
            ->setEnvPath($this->base_path . '/.env.local')
            ->setEnvPath($env_testing)
            ->loadEnv();

        $this->assertEquals('testing', $_ENV['ENV_TYPE']);

        unlink($env_local);
        unlink($env_testing);
    }

    /** @test */
    public function validate_env_can_be_used_in_method_chain_before_run(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR=validated_value");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($_ENV)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals('validated_value', $_ENV['TEST_VAR']);
    }

    /** @test */
    public function set_target_env_returns_instance_for_chaining(): void
    {
        $custom_env = [];

        $web_framework = new WebFramework($this->base_path);
        $result = $web_framework->setEnvTarget($custom_env);

        $this->assertSame($web_framework, $result);
    }

    /** @test */
    public function set_target_env_binds_to_custom_array(): void
    {
        file_put_contents($this->temp_env_file, "CUSTOM_VAR_1=value1\nCUSTOM_VAR_2=value2");

        $custom_env = [];

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($custom_env)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals('value1', $custom_env['CUSTOM_VAR_1']);
        $this->assertEquals('value2', $custom_env['CUSTOM_VAR_2']);
    }

    /** @test */
    public function set_target_env_can_be_called_late_in_chain(): void
    {
        file_put_contents($this->temp_env_file, "LATE_VAR=late_value");

        $custom_env = [];

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->setEnvTarget($custom_env)
            ->loadEnv();

        $this->assertEquals('late_value', $custom_env['LATE_VAR']);
    }

    /** @test */
    public function set_target_env_can_switch_target_between_calls(): void
    {
        file_put_contents($this->temp_env_file, "SWITCH_VAR_FIRST=switch_value");

        $env_a = [];
        $env_b = [];

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvTarget($env_a)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals('switch_value', $env_a['SWITCH_VAR_FIRST']);
        $this->assertArrayNotHasKey('SWITCH_VAR_FIRST', $env_b);

        file_put_contents($this->temp_env_file, "SWITCH_VAR_SECOND=new_value");

        $web_framework
            ->setEnvTarget($env_b)
            ->setEnvPath($this->temp_env_file)
            ->setEnvParser(EnvParser::handle())
            ->setEnvBinder(EnvBinderImmutable::handle())
            ->loadEnv();

        $this->assertEquals('new_value', $env_b['SWITCH_VAR_SECOND']);
        $this->assertArrayNotHasKey('SWITCH_VAR_SECOND', $env_a);
    }
}