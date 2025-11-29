<?php

namespace Tests\Unit;

use Tests\TestCase;
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
        $test_keys = ['TEST_VAR', 'TEST_VAR_1', 'TEST_VAR_2', 'TEST_VAR_3',
                      'APP_NAME', 'APP_ENV', 'DB_HOST', 'DB_PORT',
                      'IMMUTABLE_VAR', 'EXISTING_VAR', 'NEW_VAR',
                      'CUSTOM_VAR_1', 'CUSTOM_VAR_2'];

        foreach ($test_keys as $key) {
            unset($_ENV[$key]);
            putenv($key);
        }

        parent::tearDown();
    }

    /** @test */
    public function constructor_sets_base_path(): void
    {
        $web_framework = new WebFramework('/var/www/html');

        $this->assertInstanceOf(WebFramework::class, $web_framework);
    }

    /** @test */
    public function set_env_path_returns_instance_for_chaining(): void
    {
        $web_framework = new WebFramework($this->base_path);

        $result = $web_framework->setEnvPath($this->temp_env_file);

        $this->assertInstanceOf(WebFramework::class, $result);
        $this->assertSame($web_framework, $result);
    }

    /** @test */
    public function set_env_parses_environment_file_with_default_callable(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR_1=value1\nTEST_VAR_2=value2");

        $web_framework = new WebFramework($this->base_path);
        $result = $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnv();

        $this->assertInstanceOf(WebFramework::class, $result);
        $this->assertSame($web_framework, $result);
    }

    /** @test */
    public function set_env_accepts_custom_callable(): void
    {
        $custom_env = ['CUSTOM_VAR_1' => 'custom_value_1', 'CUSTOM_VAR_2' => 'custom_value_2'];

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnv(function ($env_path) use ($custom_env) {
                return $custom_env;
            });

        $this->assertInstanceOf(WebFramework::class, $web_framework);
    }

    /** @test */
    public function bind_envs_to_globals_immutable_sets_env_variables(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR_1=test_value_1\nTEST_VAR_2=test_value_2");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnv()
            ->bindEnvsToGlobalsImmutable();

        $this->assertEquals('test_value_1', $_ENV['TEST_VAR_1']);
        $this->assertEquals('test_value_2', $_ENV['TEST_VAR_2']);
        $this->assertEquals('test_value_1', getenv('TEST_VAR_1'));
        $this->assertEquals('test_value_2', getenv('TEST_VAR_2'));
    }

    /** @test */
    public function bind_envs_to_globals_immutable_does_not_overwrite_existing_env_variables(): void
    {
        $_ENV['EXISTING_VAR'] = 'original_value';
        putenv('EXISTING_VAR=original_value');

        file_put_contents($this->temp_env_file, "EXISTING_VAR=new_value\nNEW_VAR=new_value");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnv()
            ->bindEnvsToGlobalsImmutable();

        $this->assertEquals('original_value', $_ENV['EXISTING_VAR']);
        $this->assertEquals('original_value', getenv('EXISTING_VAR'));
        $this->assertEquals('new_value', $_ENV['NEW_VAR']);
        $this->assertEquals('new_value', getenv('NEW_VAR'));
    }

    /** @test */
    public function bind_envs_to_globals_immutable_does_not_overwrite_when_var_exists_in_env_array_only(): void
    {
        $_ENV['IMMUTABLE_VAR'] = 'env_array_value';

        file_put_contents($this->temp_env_file, "IMMUTABLE_VAR=file_value");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnv()
            ->bindEnvsToGlobalsImmutable();

        $this->assertEquals('env_array_value', $_ENV['IMMUTABLE_VAR']);
    }

    /** @test */
    public function bind_envs_to_globals_immutable_does_not_overwrite_when_var_exists_in_getenv_only(): void
    {
        putenv('IMMUTABLE_VAR=getenv_value');

        file_put_contents($this->temp_env_file, "IMMUTABLE_VAR=file_value");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnv()
            ->bindEnvsToGlobalsImmutable();

        $this->assertEquals('getenv_value', getenv('IMMUTABLE_VAR'));
    }

    /** @test */
    public function bind_envs_to_globals_immutable_returns_instance_for_chaining(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR=value");

        $web_framework = new WebFramework($this->base_path);
        $result = $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnv()
            ->bindEnvsToGlobalsImmutable();

        $this->assertInstanceOf(WebFramework::class, $result);
        $this->assertSame($web_framework, $result);
    }

    /** @test */
    public function bind_envs_to_globals_immutable_accepts_custom_callable(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR=value");

        $custom_bound_vars = [];

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnv()
            ->bindEnvsToGlobalsImmutable(function ($env) use (&$custom_bound_vars) {
                $custom_bound_vars = $env;
            });

        $this->assertArrayHasKey('TEST_VAR', $custom_bound_vars);
        $this->assertEquals('value', $custom_bound_vars['TEST_VAR']);
    }

    /** @test */
    public function method_chaining_works_for_full_workflow(): void
    {
        file_put_contents($this->temp_env_file, "APP_NAME=TestApp\nAPP_ENV=testing");

        $web_framework = (new WebFramework($this->base_path))
            ->setEnvPath($this->temp_env_file)
            ->setEnv()
            ->bindEnvsToGlobalsImmutable();

        $this->assertInstanceOf(WebFramework::class, $web_framework);
        $this->assertEquals('TestApp', $_ENV['APP_NAME']);
        $this->assertEquals('testing', $_ENV['APP_ENV']);
    }

    /** @test */
    public function handles_env_file_with_empty_lines(): void
    {
        $content = "TEST_VAR_1=value1\n\nTEST_VAR_2=value2\n\n\nTEST_VAR_3=value3";
        file_put_contents($this->temp_env_file, $content);

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnv()
            ->bindEnvsToGlobalsImmutable();

        $this->assertEquals('value1', $_ENV['TEST_VAR_1']);
        $this->assertEquals('value2', $_ENV['TEST_VAR_2']);
        $this->assertEquals('value3', $_ENV['TEST_VAR_3']);
    }

    /** @test */
    public function handles_env_file_with_special_characters(): void
    {
        $content = "DB_HOST=localhost\nDB_PORT=3306\nAPP_NAME=\"My App Name\"";
        file_put_contents($this->temp_env_file, $content);

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnv()
            ->bindEnvsToGlobalsImmutable();

        $this->assertEquals('localhost', $_ENV['DB_HOST']);
        $this->assertEquals('3306', $_ENV['DB_PORT']);
    }

    /** @test */
    public function custom_callable_for_set_env_receives_env_path(): void
    {
        $received_path = null;

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnv(function ($env_path) use (&$received_path) {
                $received_path = $env_path;
                return [];
            });

        $this->assertEquals($this->temp_env_file, $received_path);
    }

    /** @test */
    public function custom_callable_for_bind_envs_receives_env_array(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR_1=value1\nTEST_VAR_2=value2");

        $received_env = null;

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnv()
            ->bindEnvsToGlobalsImmutable(function ($env) use (&$received_env) {
                $received_env = $env;
            });

        $this->assertIsArray($received_env);
        $this->assertArrayHasKey('TEST_VAR_1', $received_env);
        $this->assertArrayHasKey('TEST_VAR_2', $received_env);
    }

    /** @test */
    public function can_call_set_env_multiple_times(): void
    {
        file_put_contents($this->temp_env_file, "TEST_VAR=first_value");

        $web_framework = new WebFramework($this->base_path);
        $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnv();

        file_put_contents($this->temp_env_file, "TEST_VAR=second_value");

        $web_framework
            ->setEnvPath($this->temp_env_file)
            ->setEnv()
            ->bindEnvsToGlobalsImmutable();

        $this->assertEquals('second_value', $_ENV['TEST_VAR']);
    }
}