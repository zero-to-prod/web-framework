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
    public function parse_from_string_parses_and_binds_env_variables(): void
    {
        $env_content = "BINDER_KEY1=value1\nBINDER_KEY2=value2";
        $target_env = [];

        $result = EnvBinderImmutable::parseFromString($env_content, $target_env);

        $this->assertEquals(['BINDER_KEY1' => 'value1', 'BINDER_KEY2' => 'value2'], $result);
        $this->assertEquals('value1', $target_env['BINDER_KEY1']);
        $this->assertEquals('value2', $target_env['BINDER_KEY2']);
        $this->assertEquals('value1', getenv('BINDER_KEY1'));
        $this->assertEquals('value2', getenv('BINDER_KEY2'));
    }

    /** @test */
    public function parse_from_string_returns_parsed_array(): void
    {
        $env_content = "BINDER_KEY1=value1\nBINDER_KEY2=value2\nBINDER_KEY3=value3";
        $target_env = [];

        $result = EnvBinderImmutable::parseFromString($env_content, $target_env);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('BINDER_KEY1', $result);
        $this->assertArrayHasKey('BINDER_KEY2', $result);
        $this->assertArrayHasKey('BINDER_KEY3', $result);
    }

    /** @test */
    public function parse_from_string_respects_immutability(): void
    {
        $target_env = ['EXISTING_ENV_KEY' => 'original_value'];
        putenv('EXISTING_GETENV_KEY=original_getenv_value');

        $env_content = "EXISTING_ENV_KEY=new_value\nEXISTING_GETENV_KEY=new_getenv_value\nNEW_KEY=new_value";

        $result = EnvBinderImmutable::parseFromString($env_content, $target_env);

        $this->assertEquals('original_value', $target_env['EXISTING_ENV_KEY']);
        $this->assertEquals('original_getenv_value', getenv('EXISTING_GETENV_KEY'));
        $this->assertEquals('new_value', $target_env['NEW_KEY']);
        $this->assertArrayHasKey('EXISTING_ENV_KEY', $result);
        $this->assertArrayHasKey('EXISTING_GETENV_KEY', $result);
        $this->assertArrayHasKey('NEW_KEY', $result);
    }

    /** @test */
    public function parse_from_string_handles_empty_string(): void
    {
        $target_env = [];

        $result = EnvBinderImmutable::parseFromString('', $target_env);

        $this->assertEquals([], $result);
        $this->assertEquals([], $target_env);
    }

    /** @test */
    public function parse_from_string_handles_empty_lines(): void
    {
        $env_content = "BINDER_KEY1=value1\n\nBINDER_KEY2=value2\n\n\nBINDER_KEY3=value3";
        $target_env = [];

        $result = EnvBinderImmutable::parseFromString($env_content, $target_env);

        $this->assertCount(3, $result);
        $this->assertEquals('value1', $target_env['BINDER_KEY1']);
        $this->assertEquals('value2', $target_env['BINDER_KEY2']);
        $this->assertEquals('value3', $target_env['BINDER_KEY3']);
    }

    /** @test */
    public function parse_from_string_handles_special_characters(): void
    {
        $env_content = "BINDER_KEY1=value with spaces\nBINDER_KEY2=value=with=equals";
        $target_env = [];

        $result = EnvBinderImmutable::parseFromString($env_content, $target_env);

        $this->assertEquals('value with spaces', $target_env['BINDER_KEY1']);
        $this->assertEquals('value=with=equals', $target_env['BINDER_KEY2']);
    }

    /** @test */
    public function parse_from_string_can_be_called_multiple_times(): void
    {
        $target_env = [];

        $result1 = EnvBinderImmutable::parseFromString("MULTI_BIND_KEY1=value1", $target_env);
        $result2 = EnvBinderImmutable::parseFromString("MULTI_BIND_KEY2=value2", $target_env);

        $this->assertEquals(['MULTI_BIND_KEY1' => 'value1'], $result1);
        $this->assertEquals(['MULTI_BIND_KEY2' => 'value2'], $result2);
        $this->assertEquals('value1', $target_env['MULTI_BIND_KEY1']);
        $this->assertEquals('value2', $target_env['MULTI_BIND_KEY2']);
    }

    /** @test */
    public function parse_from_string_preserves_first_value_on_subsequent_calls(): void
    {
        $target_env = [];

        EnvBinderImmutable::parseFromString("PRESERVE_KEY=first_value", $target_env);
        $result = EnvBinderImmutable::parseFromString("PRESERVE_KEY=second_value", $target_env);

        $this->assertEquals('second_value', $result['PRESERVE_KEY']);
        $this->assertEquals('first_value', $target_env['PRESERVE_KEY']);
        $this->assertEquals('first_value', getenv('PRESERVE_KEY'));
    }
}
