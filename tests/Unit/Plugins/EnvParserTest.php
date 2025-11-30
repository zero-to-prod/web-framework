<?php

namespace Tests\Unit\Plugins;

use Tests\TestCase;
use Zerotoprod\WebFramework\Plugins\EnvParser;

class EnvParserTest extends TestCase
{
    /** @test */
    public function handle_returns_callable(): void
    {
        $parser = EnvParser::handle();

        $this->assertIsCallable($parser);
    }

    /** @test */
    public function parser_accepts_string_and_returns_array(): void
    {
        $parser = EnvParser::handle();
        $content = "TEST_VAR=test_value";

        $result = $parser($content);

        $this->assertIsArray($result);
    }

    /** @test */
    public function parser_parses_simple_key_value_pairs(): void
    {
        $parser = EnvParser::handle();
        $content = "KEY1=value1\nKEY2=value2\nKEY3=value3";

        $result = $parser($content);

        $this->assertEquals([
            'KEY1' => 'value1',
            'KEY2' => 'value2',
            'KEY3' => 'value3',
        ], $result);
    }

    /** @test */
    public function parser_handles_empty_lines(): void
    {
        $parser = EnvParser::handle();
        $content = "KEY1=value1\n\nKEY2=value2\n\n\nKEY3=value3";

        $result = $parser($content);

        $this->assertEquals([
            'KEY1' => 'value1',
            'KEY2' => 'value2',
            'KEY3' => 'value3',
        ], $result);
    }

    /** @test */
    public function parser_handles_unix_line_endings(): void
    {
        $parser = EnvParser::handle();
        $content = "KEY1=value1\nKEY2=value2\nKEY3=value3";

        $result = $parser($content);

        $this->assertArrayHasKey('KEY1', $result);
        $this->assertArrayHasKey('KEY2', $result);
        $this->assertArrayHasKey('KEY3', $result);
    }

    /** @test */
    public function parser_handles_windows_line_endings(): void
    {
        $parser = EnvParser::handle();
        $content = "KEY1=value1\r\nKEY2=value2\r\nKEY3=value3";

        $result = $parser($content);

        $this->assertArrayHasKey('KEY1', $result);
        $this->assertArrayHasKey('KEY2', $result);
        $this->assertArrayHasKey('KEY3', $result);
    }

    /** @test */
    public function parser_handles_mac_line_endings(): void
    {
        $parser = EnvParser::handle();
        $content = "KEY1=value1\rKEY2=value2\rKEY3=value3";

        $result = $parser($content);

        $this->assertArrayHasKey('KEY1', $result);
        $this->assertArrayHasKey('KEY2', $result);
        $this->assertArrayHasKey('KEY3', $result);
    }

    /** @test */
    public function parser_handles_mixed_line_endings(): void
    {
        $parser = EnvParser::handle();
        $content = "KEY1=value1\nKEY2=value2\r\nKEY3=value3\rKEY4=value4";

        $result = $parser($content);

        $this->assertArrayHasKey('KEY1', $result);
        $this->assertArrayHasKey('KEY2', $result);
        $this->assertArrayHasKey('KEY3', $result);
        $this->assertArrayHasKey('KEY4', $result);
    }

    /** @test */
    public function parser_returns_empty_array_for_empty_content(): void
    {
        $parser = EnvParser::handle();
        $content = "";

        $result = $parser($content);

        $this->assertEquals([], $result);
    }

    /** @test */
    public function parser_handles_values_with_spaces(): void
    {
        $parser = EnvParser::handle();
        $content = "APP_NAME=My Application";

        $result = $parser($content);

        $this->assertEquals('My Application', $result['APP_NAME']);
    }

    /** @test */
    public function parser_handles_quoted_values(): void
    {
        $parser = EnvParser::handle();
        $content = "APP_NAME=\"My Application\"\nDB_NAME='database'";

        $result = $parser($content);

        $this->assertArrayHasKey('APP_NAME', $result);
        $this->assertArrayHasKey('DB_NAME', $result);
    }

    /** @test */
    public function parser_handles_values_with_equals_signs(): void
    {
        $parser = EnvParser::handle();
        $content = "CONNECTION_STRING=server=localhost;user=root";

        $result = $parser($content);

        $this->assertArrayHasKey('CONNECTION_STRING', $result);
    }

    /** @test */
    public function parser_handles_empty_values(): void
    {
        $parser = EnvParser::handle();
        $content = "EMPTY_VAR=\nANOTHER_VAR=value";

        $result = $parser($content);

        $this->assertArrayHasKey('EMPTY_VAR', $result);
        $this->assertArrayHasKey('ANOTHER_VAR', $result);
    }

    /** @test */
    public function parser_can_be_called_multiple_times(): void
    {
        $parser = EnvParser::handle();
        $content1 = "KEY1=value1";
        $content2 = "KEY2=value2";

        $result1 = $parser($content1);
        $result2 = $parser($content2);

        $this->assertEquals(['KEY1' => 'value1'], $result1);
        $this->assertEquals(['KEY2' => 'value2'], $result2);
    }

    /** @test */
    public function multiple_parser_instances_work_independently(): void
    {
        $parser1 = EnvParser::handle();
        $parser2 = EnvParser::handle();

        $result1 = $parser1("KEY1=value1");
        $result2 = $parser2("KEY2=value2");

        $this->assertEquals(['KEY1' => 'value1'], $result1);
        $this->assertEquals(['KEY2' => 'value2'], $result2);
    }

    /** @test */
    public function parser_handles_content_with_only_newlines(): void
    {
        $parser = EnvParser::handle();
        $content = "\n\n\n";

        $result = $parser($content);

        $this->assertEquals([], $result);
    }

    /** @test */
    public function parser_handles_content_with_whitespace_lines(): void
    {
        $parser = EnvParser::handle();
        $content = "KEY1=value1\n   \nKEY2=value2";

        $result = $parser($content);

        $this->assertArrayHasKey('KEY1', $result);
        $this->assertArrayHasKey('KEY2', $result);
    }
}
