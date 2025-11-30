<?php

namespace Tests\Unit\Plugins;

use Tests\TestCase;
use Zerotoprod\WebFramework\Plugins\EnvBinderImmutable;

class EnvBinderImmutableTest extends TestCase
{
    protected function tearDown(): void
    {
        $test_keys = [
            'BINDER_KEY1',
            'BINDER_KEY2',
            'BINDER_KEY3',
            'EXISTING_ENV_KEY',
            'EXISTING_GETENV_KEY',
            'NEW_KEY',
            'EMPTY_VALUE_KEY',
            'MULTI_BIND_KEY1',
            'MULTI_BIND_KEY2',
            'PRESERVE_KEY',
            'OVERWRITE_ATTEMPT_KEY'
        ];

        foreach ($test_keys as $key) {
            unset($_ENV[$key]);
            putenv($key);
        }

        parent::tearDown();
    }

    /** @test */
    public function handle_returns_callable(): void
    {
        $binder = EnvBinderImmutable::handle();

        $this->assertIsCallable($binder);
    }

    /** @test */
    public function binder_binds_variables_to_target_env(): void
    {
        $binder = EnvBinderImmutable::handle();
        $parsed_env = [
            'BINDER_KEY1' => 'value1',
            'BINDER_KEY2' => 'value2',
        ];
        $target_env = [];

        $binder($parsed_env, $target_env);

        $this->assertEquals('value1', $target_env['BINDER_KEY1']);
        $this->assertEquals('value2', $target_env['BINDER_KEY2']);
    }

    /** @test */
    public function binder_sets_variables_in_getenv(): void
    {
        $binder = EnvBinderImmutable::handle();
        $parsed_env = [
            'BINDER_KEY1' => 'value1',
            'BINDER_KEY2' => 'value2',
        ];
        $target_env = [];

        $binder($parsed_env, $target_env);

        $this->assertEquals('value1', getenv('BINDER_KEY1'));
        $this->assertEquals('value2', getenv('BINDER_KEY2'));
    }

    /** @test */
    public function binder_does_not_overwrite_existing_env_array_values(): void
    {
        $binder = EnvBinderImmutable::handle();
        $parsed_env = [
            'EXISTING_ENV_KEY' => 'new_value',
        ];
        $target_env = [
            'EXISTING_ENV_KEY' => 'original_value',
        ];

        $binder($parsed_env, $target_env);

        $this->assertEquals('original_value', $target_env['EXISTING_ENV_KEY']);
    }

    /** @test */
    public function binder_does_not_overwrite_existing_getenv_values(): void
    {
        putenv('EXISTING_GETENV_KEY=original_value');

        $binder = EnvBinderImmutable::handle();
        $parsed_env = [
            'EXISTING_GETENV_KEY' => 'new_value',
        ];
        $target_env = [];

        $binder($parsed_env, $target_env);

        $this->assertEquals('original_value', getenv('EXISTING_GETENV_KEY'));
        $this->assertArrayNotHasKey('EXISTING_GETENV_KEY', $target_env);
    }

    /** @test */
    public function binder_does_not_overwrite_when_exists_in_both_env_and_getenv(): void
    {
        $_ENV['EXISTING_ENV_KEY'] = 'env_value';
        putenv('EXISTING_ENV_KEY=getenv_value');

        $binder = EnvBinderImmutable::handle();
        $parsed_env = [
            'EXISTING_ENV_KEY' => 'new_value',
        ];
        $target_env = &$_ENV;

        $binder($parsed_env, $target_env);

        $this->assertEquals('env_value', $_ENV['EXISTING_ENV_KEY']);
        $this->assertEquals('getenv_value', getenv('EXISTING_ENV_KEY'));
    }

    /** @test */
    public function binder_handles_empty_parsed_env(): void
    {
        $binder = EnvBinderImmutable::handle();
        $parsed_env = [];
        $target_env = [];

        $binder($parsed_env, $target_env);

        $this->assertEquals([], $target_env);
    }

    /** @test */
    public function binder_handles_empty_string_values(): void
    {
        $binder = EnvBinderImmutable::handle();
        $parsed_env = [
            'EMPTY_VALUE_KEY' => '',
        ];
        $target_env = [];

        $binder($parsed_env, $target_env);

        $this->assertArrayHasKey('EMPTY_VALUE_KEY', $target_env);
        $this->assertEquals('', $target_env['EMPTY_VALUE_KEY']);
        $this->assertEquals('', getenv('EMPTY_VALUE_KEY'));
    }

    /** @test */
    public function binder_can_be_called_multiple_times(): void
    {
        $binder = EnvBinderImmutable::handle();
        $target_env = [];

        $parsed_env1 = ['MULTI_BIND_KEY1' => 'value1'];
        $binder($parsed_env1, $target_env);

        $parsed_env2 = ['MULTI_BIND_KEY2' => 'value2'];
        $binder($parsed_env2, $target_env);

        $this->assertEquals('value1', $target_env['MULTI_BIND_KEY1']);
        $this->assertEquals('value2', $target_env['MULTI_BIND_KEY2']);
    }

    /** @test */
    public function binder_preserves_first_value_on_multiple_calls_with_same_key(): void
    {
        $binder = EnvBinderImmutable::handle();
        $target_env = [];

        $parsed_env1 = ['PRESERVE_KEY' => 'first_value'];
        $binder($parsed_env1, $target_env);

        $parsed_env2 = ['PRESERVE_KEY' => 'second_value'];
        $binder($parsed_env2, $target_env);

        $this->assertEquals('first_value', $target_env['PRESERVE_KEY']);
        $this->assertEquals('first_value', getenv('PRESERVE_KEY'));
    }

    /** @test */
    public function multiple_binder_instances_work_independently(): void
    {
        $binder1 = EnvBinderImmutable::handle();
        $binder2 = EnvBinderImmutable::handle();

        $target_env1 = [];
        $target_env2 = [];

        $binder1(['BINDER_KEY1' => 'value1'], $target_env1);
        $binder2(['BINDER_KEY2' => 'value2'], $target_env2);

        $this->assertEquals('value1', $target_env1['BINDER_KEY1']);
        $this->assertArrayNotHasKey('BINDER_KEY2', $target_env1);

        $this->assertEquals('value2', $target_env2['BINDER_KEY2']);
        $this->assertArrayNotHasKey('BINDER_KEY1', $target_env2);
    }

    /** @test */
    public function binder_maintains_reference_to_target_env(): void
    {
        $binder = EnvBinderImmutable::handle();
        $target_env = [];

        $binder(['BINDER_KEY1' => 'value1'], $target_env);

        $this->assertArrayHasKey('BINDER_KEY1', $target_env);
    }

    /** @test */
    public function binder_binds_to_global_env_array(): void
    {
        $binder = EnvBinderImmutable::handle();
        $parsed_env = ['BINDER_KEY1' => 'value1'];

        $binder($parsed_env, $_ENV);

        $this->assertEquals('value1', $_ENV['BINDER_KEY1']);
    }

    /** @test */
    public function binder_respects_immutability_contract(): void
    {
        $target_env = ['OVERWRITE_ATTEMPT_KEY' => 'original'];
        putenv('OVERWRITE_ATTEMPT_KEY=original');

        $binder = EnvBinderImmutable::handle();
        $parsed_env = ['OVERWRITE_ATTEMPT_KEY' => 'attempted_overwrite'];

        $binder($parsed_env, $target_env);

        $this->assertEquals('original', $target_env['OVERWRITE_ATTEMPT_KEY']);
        $this->assertEquals('original', getenv('OVERWRITE_ATTEMPT_KEY'));
    }

    /** @test */
    public function binder_handles_numeric_string_values(): void
    {
        $binder = EnvBinderImmutable::handle();
        $parsed_env = [
            'BINDER_KEY1' => '123',
            'BINDER_KEY2' => '45.67',
        ];
        $target_env = [];

        $binder($parsed_env, $target_env);

        $this->assertEquals('123', $target_env['BINDER_KEY1']);
        $this->assertEquals('45.67', $target_env['BINDER_KEY2']);
    }

    /** @test */
    public function binder_handles_special_characters_in_values(): void
    {
        $binder = EnvBinderImmutable::handle();
        $parsed_env = [
            'BINDER_KEY1' => 'value with spaces',
            'BINDER_KEY2' => 'value=with=equals',
            'BINDER_KEY3' => 'value;with;semicolons',
        ];
        $target_env = [];

        $binder($parsed_env, $target_env);

        $this->assertEquals('value with spaces', $target_env['BINDER_KEY1']);
        $this->assertEquals('value=with=equals', $target_env['BINDER_KEY2']);
        $this->assertEquals('value;with;semicolons', $target_env['BINDER_KEY3']);
    }

    /** @test */
    public function binder_skips_variables_when_only_in_env_array(): void
    {
        $target_env = ['EXISTING_ENV_KEY' => 'existing_value'];

        $binder = EnvBinderImmutable::handle();
        $parsed_env = [
            'EXISTING_ENV_KEY' => 'new_value',
            'NEW_KEY' => 'new_key_value',
        ];

        $binder($parsed_env, $target_env);

        $this->assertEquals('existing_value', $target_env['EXISTING_ENV_KEY']);
        $this->assertEquals('new_key_value', $target_env['NEW_KEY']);
    }

    /** @test */
    public function binder_skips_variables_when_only_in_getenv(): void
    {
        putenv('EXISTING_GETENV_KEY=existing_value');

        $binder = EnvBinderImmutable::handle();
        $parsed_env = [
            'EXISTING_GETENV_KEY' => 'new_value',
            'NEW_KEY' => 'new_key_value',
        ];
        $target_env = [];

        $binder($parsed_env, $target_env);

        $this->assertArrayNotHasKey('EXISTING_GETENV_KEY', $target_env);
        $this->assertEquals('existing_value', getenv('EXISTING_GETENV_KEY'));
        $this->assertEquals('new_key_value', $target_env['NEW_KEY']);
    }
}
