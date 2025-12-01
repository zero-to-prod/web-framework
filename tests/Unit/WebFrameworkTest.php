<?php

namespace Tests\Unit;

use Psr\Container\ContainerInterface;
use Tests\TestCase;
use Zerotoprod\WebFramework\WebFramework;

class WebFrameworkTest extends TestCase
{
    /** @test */
    public function construct_with_base_path(): void
    {
        $base_path = '/path/to/app';

        $framework = new WebFramework($base_path);

        $this->assertInstanceOf(WebFramework::class, $framework);
    }

    /** @test */
    public function construct_without_base_path(): void
    {
        $framework = new WebFramework();

        $this->assertInstanceOf(WebFramework::class, $framework);
    }

    /** @test */
    public function set_env_stores_reference_and_returns_instance(): void
    {
        $env = ['APP_ENV' => 'testing'];
        $framework = new WebFramework();

        $result = $framework->setEnv($env);

        $this->assertSame($framework, $result);
    }

    /** @test */
    public function set_env_with_empty_array(): void
    {
        $env = [];
        $framework = new WebFramework();

        $result = $framework->setEnv($env);

        $this->assertSame($framework, $result);
    }

    /** @test */
    public function set_server_stores_reference_and_returns_instance(): void
    {
        $server = ['REQUEST_METHOD' => 'GET'];
        $framework = new WebFramework();

        $result = $framework->setServer($server);

        $this->assertSame($framework, $result);
    }

    /** @test */
    public function set_server_with_empty_array(): void
    {
        $server = [];
        $framework = new WebFramework();

        $result = $framework->setServer($server);

        $this->assertSame($framework, $result);
    }

    /** @test */
    public function set_container_stores_instance_and_returns_framework(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $framework = new WebFramework();

        $result = $framework->setContainer($container);

        $this->assertSame($framework, $result);
    }

    /** @test */
    public function set_container_replaces_existing_container(): void
    {
        $container1 = $this->createMock(ContainerInterface::class);
        $container2 = $this->createMock(ContainerInterface::class);
        $framework = new WebFramework();

        $framework->setContainer($container1);
        $result = $framework->setContainer($container2);

        $this->assertSame($container2, $framework->container());
        $this->assertSame($framework, $result);
    }

    /** @test */
    public function container_returns_stored_container(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $framework = new WebFramework();
        $framework->setContainer($container);

        $result = $framework->container();

        $this->assertSame($container, $result);
    }

    /** @test */
    public function container_when_not_set_returns_null(): void
    {
        $framework = new WebFramework();

        $result = $framework->container();

        $this->assertNull($result);
    }

    /** @test */
    public function context_executes_callable_with_framework_instance(): void
    {
        $framework = new WebFramework();
        $received = null;

        $result = $framework->context(function ($fw) use (&$received) {
            $received = $fw;
        });

        $this->assertSame($framework, $received);
        $this->assertSame($framework, $result);
    }

    /** @test */
    public function context_with_no_op_callable_returns_instance(): void
    {
        $framework = new WebFramework();

        $result = $framework->context(function ($fw) {
            // No-op
        });

        $this->assertSame($framework, $result);
    }
}
