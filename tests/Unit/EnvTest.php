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
            'CUSTOM_VAR_2'
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

        $result = $web_framework->envPathSet($this->temp_env_file);

        $this->assertSame($web_framework, $result);
    }

    /** @test */
    public function env_parser_set_returns_instance_for_chaining(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR_1=value1\nTEST_VAR_2=value2");

        $web_framework = new WebFramework($this->base_path);
        $result = $web_framework
            ->envPathSet($this->temp_env_file)
            ->envParserSet(EnvParser::handle());

        $this->assertSame($web_framework, $result);
    }

    /** @test */
    public function run_sets_env_variables_to_globals(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR_1=test_value_1\nTEST_VAR_2=test_value_2");

        $web_framework = new WebFramework($this->base_path, $_ENV);
        $web_framework
            ->envPathSet($this->temp_env_file)
            ->envParserSet(EnvParser::handle())
            ->envBinderSet(EnvBinderImmutable::handle())
            ->run();

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

        $web_framework = new WebFramework($this->base_path, $_ENV);
        $web_framework
            ->envPathSet($this->temp_env_file)
            ->envParserSet(EnvParser::handle())
            ->envBinderSet(EnvBinderImmutable::handle())
            ->run();

        $this->assertEquals('original_value', getenv('EXISTING_VAR'));
        $this->assertEquals('new_value', $_ENV['NEW_VAR']);
        $this->assertEquals('new_value', getenv('NEW_VAR'));
    }

    /** @test */
    public function run_with_immutable_binder_does_not_overwrite_when_var_exists_in_env_array_only(): void
    {
        $_ENV['IMMUTABLE_VAR'] = 'env_array_value';

        file_put_contents($this->temp_env_file, "IMMUTABLE_VAR=file_value");

        $web_framework = new WebFramework($this->base_path, $_ENV);
        $web_framework
            ->envPathSet($this->temp_env_file)
            ->envParserSet(EnvParser::handle())
            ->envBinderSet(EnvBinderImmutable::handle())
            ->run();

        $this->assertEquals('env_array_value', $_ENV['IMMUTABLE_VAR']);
    }

    /** @test */
    public function run_with_immutable_binder_does_not_overwrite_when_var_exists_in_getenv_only(): void
    {
        putenv('IMMUTABLE_VAR=getenv_value');

        file_put_contents($this->temp_env_file, "IMMUTABLE_VAR=file_value");

        $web_framework = new WebFramework($this->base_path, $_ENV);
        $web_framework
            ->envPathSet($this->temp_env_file)
            ->envParserSet(EnvParser::handle())
            ->envBinderSet(EnvBinderImmutable::handle())
            ->run();

        $this->assertEquals('getenv_value', getenv('IMMUTABLE_VAR'));
    }

    /** @test */
    public function run_returns_instance_for_chaining(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR=value");

        $web_framework = new WebFramework($this->base_path, $_ENV);
        $result = $web_framework
            ->envPathSet($this->temp_env_file)
            ->envParserSet(EnvParser::handle())
            ->envBinderSet(EnvBinderImmutable::handle())
            ->run();

        $this->assertSame($web_framework, $result);
    }

    /** @test */
    public function env_binder_set_accepts_custom_callable(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR=value");

        $custom_bound_vars = [];

        $web_framework = new WebFramework($this->base_path, $_ENV);
        $web_framework
            ->envPathSet($this->temp_env_file)
            ->envParserSet(EnvParser::handle())
            ->envBinderSet(function ($parsed_env, &$target_env) use (&$custom_bound_vars) {
                $custom_bound_vars = $parsed_env;
            })
            ->run();

        $this->assertArrayHasKey('TEST_VAR', $custom_bound_vars);
        $this->assertEquals('value', $custom_bound_vars['TEST_VAR']);
    }

    /** @test */
    public function method_chaining_works_for_full_workflow(): void
    {
        file_put_contents($this->temp_env_file, "APP_NAME=TestApp\nAPP_ENV=testing");

        $web_framework = (new WebFramework($this->base_path, $_ENV))
            ->envPathSet($this->temp_env_file)
            ->envParserSet(EnvParser::handle())
            ->envBinderSet(EnvBinderImmutable::handle())
            ->run();

        $this->assertEquals('TestApp', $_ENV['APP_NAME']);
        $this->assertEquals('testing', $_ENV['APP_ENV']);
    }

    /** @test */
    public function run_handles_env_file_with_empty_lines(): void
    {
        $content = "TEST_VAR_1=value1\n\nTEST_VAR_2=value2\n\n\nTEST_VAR_3=value3";
        file_put_contents($this->temp_env_file, $content);

        $web_framework = new WebFramework($this->base_path, $_ENV);
        $web_framework
            ->envPathSet($this->temp_env_file)
            ->envParserSet(EnvParser::handle())
            ->envBinderSet(EnvBinderImmutable::handle())
            ->run();

        $this->assertEquals('value1', $_ENV['TEST_VAR_1']);
        $this->assertEquals('value2', $_ENV['TEST_VAR_2']);
        $this->assertEquals('value3', $_ENV['TEST_VAR_3']);
    }

    /** @test */
    public function run_handles_env_file_with_special_characters(): void
    {
        $content = "DB_HOST=localhost\nDB_PORT=3306\nAPP_NAME=\"My App Name\"";
        file_put_contents($this->temp_env_file, $content);

        $web_framework = new WebFramework($this->base_path, $_ENV);
        $web_framework
            ->envPathSet($this->temp_env_file)
            ->envParserSet(EnvParser::handle())
            ->envBinderSet(EnvBinderImmutable::handle())
            ->run();

        $this->assertEquals('localhost', $_ENV['DB_HOST']);
        $this->assertEquals('3306', $_ENV['DB_PORT']);
    }

    /** @test */
    public function custom_parser_receives_env_path(): void
    {
        $received_path = null;

        $web_framework = new WebFramework($this->base_path, $_ENV);
        $web_framework
            ->envPathSet($this->temp_env_file)
            ->envParserSet(function ($env_path) use (&$received_path) {
                $received_path = $env_path;

                return [];
            })
            ->envBinderSet(EnvBinderImmutable::handle())
            ->run();

        $this->assertEquals($this->temp_env_file, $received_path);
    }

    /** @test */
    public function custom_binder_receives_parsed_env_and_target_env(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR_1=value1\nTEST_VAR_2=value2");

        $received_parsed_env = null;
        $received_target_env = null;

        $web_framework = new WebFramework($this->base_path, $_ENV);
        $web_framework
            ->envPathSet($this->temp_env_file)
            ->envParserSet(EnvParser::handle())
            ->envBinderSet(function ($parsed_env, &$target_env) use (&$received_parsed_env, &$received_target_env) {
                $received_parsed_env = $parsed_env;
                $received_target_env = $target_env;
            })
            ->run();

        $this->assertIsArray($received_parsed_env);
        $this->assertArrayHasKey('TEST_VAR_1', $received_parsed_env);
        $this->assertArrayHasKey('TEST_VAR_2', $received_parsed_env);
        $this->assertIsArray($received_target_env);
    }

    /** @test */
    public function can_call_run_multiple_times(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR=first_value");

        $web_framework = new WebFramework($this->base_path, $_ENV);
        $web_framework
            ->envPathSet($this->temp_env_file)
            ->envParserSet(EnvParser::handle())
            ->envBinderSet(EnvBinderImmutable::handle())
            ->run();

        $this->assertEquals('first_value', $_ENV['TEST_VAR']);

        // Unset to allow the immutable binder to set it again
        unset($_ENV['TEST_VAR']);
        putenv('TEST_VAR');

        file_put_contents($this->temp_env_file, "TEST_VAR=second_value");

        $web_framework
            ->envPathSet($this->temp_env_file)
            ->envParserSet(EnvParser::handle())
            ->envBinderSet(EnvBinderImmutable::handle())
            ->run();

        $this->assertEquals('second_value', $_ENV['TEST_VAR']);
    }

    /** @test */
    public function can_inject_custom_env_array(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR_1=value1\nTEST_VAR_2=value2");

        $custom_env = [];

        $web_framework = new WebFramework($this->base_path, $custom_env);
        $web_framework
            ->envPathSet($this->temp_env_file)
            ->envParserSet(EnvParser::handle())
            ->envBinderSet(EnvBinderImmutable::handle())
            ->run();

        $this->assertEquals('value1', $custom_env['TEST_VAR_1']);
        $this->assertEquals('value2', $custom_env['TEST_VAR_2']);
        $this->assertArrayNotHasKey('TEST_VAR_1', $_ENV);
        $this->assertArrayNotHasKey('TEST_VAR_2', $_ENV);
    }
}