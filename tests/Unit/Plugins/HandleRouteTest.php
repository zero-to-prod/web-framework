<?php

namespace Tests\Unit\Plugins;

use Tests\TestCase;
use Zerotoprod\WebFramework\Plugins\HandleRoute;

class TestController
{
    public static $call_count = 0;
    public static $received_server = null;

    public function index(array $server = []): void
    {
        self::$call_count++;
        self::$received_server = $server;
    }

    public function show(array $server = []): void
    {
        echo 'Controller response';
        self::$received_server = $server;
    }

    public function create(): void
    {
        echo 'Created resource';
    }

    public function store(array $server = []): void
    {
        self::$call_count++;
        self::$received_server = $server;
    }

    public function update(): void
    {
        echo 'Updated resource';
    }

    public function destroy(array $server = []): void
    {
        self::$call_count++;
        self::$received_server = $server;
    }
}

class AnotherController
{
    public static $executed = false;

    public function handle(): void
    {
        self::$executed = true;
    }
}

class HandleRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TestController::$call_count = 0;
        TestController::$received_server = null;
        AnotherController::$executed = false;
    }
    /** @test */
    public function can_instantiate_with_server_target(): void
    {
        $server = [];

        $router = new HandleRoute($server);

        $this->assertInstanceOf(HandleRoute::class, $router);
    }

    /** @test */
    public function get_method_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->get('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function post_method_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->post('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function put_method_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->put('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function patch_method_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->patch('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function delete_method_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->delete('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function options_method_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->options('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function head_method_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->head('/test');

        $this->assertSame($router, $result);
    }

    /** @test */
    public function get_route_matches_and_executes_callable(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/home',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertTrue($executed);
    }

    /** @test */
    public function post_route_matches_and_executes_callable(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/submit',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->post('/submit', function () use (&$executed) {
            $executed = true;
        });

        $this->assertTrue($executed);
    }

    /** @test */
    public function put_route_matches_and_executes_callable(): void
    {
        $server = [
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/update',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->put('/update', function () use (&$executed) {
            $executed = true;
        });

        $this->assertTrue($executed);
    }

    /** @test */
    public function patch_route_matches_and_executes_callable(): void
    {
        $server = [
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI' => '/partial-update',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->patch('/partial-update', function () use (&$executed) {
            $executed = true;
        });

        $this->assertTrue($executed);
    }

    /** @test */
    public function delete_route_matches_and_executes_callable(): void
    {
        $server = [
            'REQUEST_METHOD' => 'DELETE',
            'REQUEST_URI' => '/remove',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->delete('/remove', function () use (&$executed) {
            $executed = true;
        });

        $this->assertTrue($executed);
    }

    /** @test */
    public function options_route_matches_and_executes_callable(): void
    {
        $server = [
            'REQUEST_METHOD' => 'OPTIONS',
            'REQUEST_URI' => '/api',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->options('/api', function () use (&$executed) {
            $executed = true;
        });

        $this->assertTrue($executed);
    }

    /** @test */
    public function head_route_matches_and_executes_callable(): void
    {
        $server = [
            'REQUEST_METHOD' => 'HEAD',
            'REQUEST_URI' => '/status',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->head('/status', function () use (&$executed) {
            $executed = true;
        });

        $this->assertTrue($executed);
    }

    /** @test */
    public function route_outputs_string_when_action_is_string(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/hello',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->get('/hello', 'Hello World');
        $output = ob_get_clean();

        $this->assertEquals('Hello World', $output);
    }

    /** @test */
    public function route_does_not_execute_when_method_does_not_match(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/home',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function route_does_not_execute_when_uri_does_not_match(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/about',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function route_handles_query_string_in_uri(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/search?q=test',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/search', function () use (&$executed) {
            $executed = true;
        });

        $this->assertTrue($executed);
    }

    /** @test */
    public function route_handles_multiple_query_parameters(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/results?page=1&limit=10&sort=name',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/results', function () use (&$executed) {
            $executed = true;
        });

        $this->assertTrue($executed);
    }

    /** @test */
    public function multiple_routes_can_be_chained(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/about',
        ];
        $home_executed = false;
        $about_executed = false;

        $router = new HandleRoute($server);
        $router
            ->get('/home', function () use (&$home_executed) {
                $home_executed = true;
            })
            ->get('/about', function () use (&$about_executed) {
                $about_executed = true;
            });

        $this->assertFalse($home_executed);
        $this->assertTrue($about_executed);
    }

    /** @test */
    public function only_first_matching_route_executes(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/duplicate',
        ];
        $first_executed = false;
        $second_executed = false;

        $router = new HandleRoute($server);
        $router
            ->get('/duplicate', function () use (&$first_executed) {
                $first_executed = true;
            })
            ->get('/duplicate', function () use (&$second_executed) {
                $second_executed = true;
            });

        $this->assertTrue($first_executed);
        $this->assertFalse($second_executed);
    }

    /** @test */
    public function route_does_nothing_when_action_is_null(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/null-action',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->get('/null-action', null);
        $output = ob_get_clean();

        $this->assertEquals('', $output);
    }

    /** @test */
    public function route_handles_empty_server_array(): void
    {
        $server = [];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function route_handles_missing_request_method(): void
    {
        $server = [
            'REQUEST_URI' => '/home',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function route_handles_missing_request_uri(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function has_matched_returns_true_when_route_matched(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/home',
        ];

        $router = new HandleRoute($server);
        $router->get('/home', function () {
        });

        $this->assertTrue($router->hasMatched());
    }

    /** @test */
    public function has_matched_returns_false_when_no_route_matched(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/home',
        ];

        $router = new HandleRoute($server);
        $router->get('/about', function () {
        });

        $this->assertFalse($router->hasMatched());
    }

    /** @test */
    public function reset_clears_matched_state(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/home',
        ];

        $router = new HandleRoute($server);
        $router->get('/home', function () {
        });

        $this->assertTrue($router->hasMatched());

        $router->reset();

        $this->assertFalse($router->hasMatched());
    }

    /** @test */
    public function reset_returns_instance_for_chaining(): void
    {
        $server = [];
        $router = new HandleRoute($server);

        $result = $router->reset();

        $this->assertSame($router, $result);
    }

    /** @test */
    public function reset_allows_routes_to_match_again(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/home',
        ];
        $first_count = 0;
        $second_count = 0;

        $router = new HandleRoute($server);

        $router->get('/home', function () use (&$first_count) {
            $first_count++;
        });

        $router->reset();

        $router->get('/home', function () use (&$second_count) {
            $second_count++;
        });

        $this->assertEquals(1, $first_count);
        $this->assertEquals(1, $second_count);
    }

    /** @test */
    public function route_maintains_reference_to_server_array(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/initial',
        ];
        $initial_executed = false;
        $updated_executed = false;

        $router = new HandleRoute($server);

        $router->get('/initial', function () use (&$initial_executed) {
            $initial_executed = true;
        });

        $this->assertTrue($initial_executed);

        $server['REQUEST_URI'] = '/updated';
        $router->reset();

        $router->get('/updated', function () use (&$updated_executed) {
            $updated_executed = true;
        });

        $this->assertTrue($updated_executed);
    }

    /** @test */
    public function route_handles_root_path(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/', function () use (&$executed) {
            $executed = true;
        });

        $this->assertTrue($executed);
    }

    /** @test */
    public function route_is_case_sensitive_for_uri(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/Home',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function route_requires_exact_method_match(): void
    {
        $server = [
            'REQUEST_METHOD' => 'get',
            'REQUEST_URI' => '/home',
        ];
        $executed = false;

        $router = new HandleRoute($server);
        $router->get('/home', function () use (&$executed) {
            $executed = true;
        });

        $this->assertFalse($executed);
    }

    /** @test */
    public function callable_can_echo_output(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/echo-test',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->get('/echo-test', function () {
            echo 'Echoed content';
        });
        $output = ob_get_clean();

        $this->assertEquals('Echoed content', $output);
    }

    /** @test */
    public function different_methods_same_uri_handled_independently(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/resource',
        ];
        $get_executed = false;
        $post_executed = false;

        $router = new HandleRoute($server);
        $router
            ->get('/resource', function () use (&$get_executed) {
                $get_executed = true;
            })
            ->post('/resource', function () use (&$post_executed) {
                $post_executed = true;
            });

        $this->assertFalse($get_executed);
        $this->assertTrue($post_executed);
    }

    /** @test */
    public function route_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/controller',
        ];

        $router = new HandleRoute($server);
        $router->get('/controller', [TestController::class, 'index']);

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function route_executes_controller_method_and_outputs_response(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/show',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->get('/show', [TestController::class, 'show']);
        $output = ob_get_clean();

        $this->assertEquals('Controller response', $output);
    }

    /** @test */
    public function post_route_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/store',
        ];

        $router = new HandleRoute($server);
        $router->post('/store', [TestController::class, 'store']);

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function put_route_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'PUT',
            'REQUEST_URI' => '/update',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->put('/update', [TestController::class, 'update']);
        $output = ob_get_clean();

        $this->assertEquals('Updated resource', $output);
    }

    /** @test */
    public function patch_route_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'PATCH',
            'REQUEST_URI' => '/partial-update',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->patch('/partial-update', [TestController::class, 'update']);
        $output = ob_get_clean();

        $this->assertEquals('Updated resource', $output);
    }

    /** @test */
    public function delete_route_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'DELETE',
            'REQUEST_URI' => '/destroy',
        ];

        $router = new HandleRoute($server);
        $router->delete('/destroy', [TestController::class, 'destroy']);

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function options_route_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'OPTIONS',
            'REQUEST_URI' => '/resource',
        ];

        $router = new HandleRoute($server);

        ob_start();
        $router->options('/resource', [TestController::class, 'show']);
        $output = ob_get_clean();

        $this->assertEquals('Controller response', $output);
    }

    /** @test */
    public function head_route_accepts_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'HEAD',
            'REQUEST_URI' => '/resource',
        ];

        $router = new HandleRoute($server);
        $router->head('/resource', [TestController::class, 'index']);

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function multiple_routes_with_controller_array_syntax(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/another',
        ];

        $router = new HandleRoute($server);
        $router
            ->get('/test', [TestController::class, 'index'])
            ->get('/another', [AnotherController::class, 'handle']);

        $this->assertEquals(0, TestController::$call_count);
        $this->assertTrue(AnotherController::$executed);
    }

    /** @test */
    public function controller_array_syntax_only_executes_first_match(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/duplicate',
        ];

        $router = new HandleRoute($server);
        $router
            ->get('/duplicate', [TestController::class, 'index'])
            ->get('/duplicate', [TestController::class, 'store']);

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function controller_array_syntax_chains_with_other_action_types(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/mixed',
        ];
        $closure_executed = false;

        $router = new HandleRoute($server);

        ob_start();
        $router
            ->get('/closure', function () use (&$closure_executed) {
                $closure_executed = true;
            })
            ->get('/mixed', [TestController::class, 'show'])
            ->get('/string', 'String response');
        $output = ob_get_clean();

        $this->assertFalse($closure_executed);
        $this->assertEquals('Controller response', $output);
    }

    /** @test */
    public function controller_array_syntax_does_not_match_when_uri_different(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/wrong',
        ];

        $router = new HandleRoute($server);
        $router->get('/correct', [TestController::class, 'index']);

        $this->assertEquals(0, TestController::$call_count);
    }

    /** @test */
    public function controller_array_syntax_does_not_match_when_method_different(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/resource',
        ];

        $router = new HandleRoute($server);
        $router->get('/resource', [TestController::class, 'index']);

        $this->assertEquals(0, TestController::$call_count);
    }

    /** @test */
    public function controller_array_syntax_handles_query_strings(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/search?q=test&page=1',
        ];

        $router = new HandleRoute($server);
        $router->get('/search', [TestController::class, 'index']);

        $this->assertEquals(1, TestController::$call_count);
    }

    /** @test */
    public function controller_instantiated_each_time_route_matches(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
        ];

        $router = new HandleRoute($server);
        $router->get('/test', [TestController::class, 'index']);

        $this->assertEquals(1, TestController::$call_count);

        $router->reset();
        $router->get('/test', [TestController::class, 'index']);

        $this->assertEquals(2, TestController::$call_count);
    }

    /** @test */
    public function controller_array_syntax_works_with_reset(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/home',
        ];

        $router = new HandleRoute($server);
        $router->get('/home', [TestController::class, 'index']);

        $this->assertTrue($router->hasMatched());

        $router->reset();

        $this->assertFalse($router->hasMatched());

        $router->get('/home', [TestController::class, 'store']);

        $this->assertEquals(2, TestController::$call_count);
    }

    /** @test */
    public function array_action_with_null_does_not_execute(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/wrong',
        ];

        $router = new HandleRoute($server);
        $router->get('/test', [TestController::class, 'index']);

        $this->assertEquals(0, TestController::$call_count);
    }

    /** @test */
    public function benchmark_optimization_impact(): void
    {
        $iterations = 1000;
        $routes = 50;

        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $server = [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => "/route-$routes?query=test",
            ];
            $router = new HandleRoute($server);

            for ($j = 1; $j <= $routes; $j++) {
                $router->get("/route-$j", function () {
                });
            }
        }
        $duration = microtime(true) - $start;

        // Expect 2-5x improvement (baseline ~150ms, optimized <60ms)
        // Using generous limit to account for different environments
        $this->assertLessThan(0.1, $duration,
            "Expected <100ms for 1000 iterations × 50 routes, got: {$duration}s");
    }

    /** @test */
    public function closure_receives_server_target(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
            'CUSTOM_KEY' => 'custom_value',
            'HTTP_HOST' => 'example.com',
        ];
        $received_server = null;

        $router = new HandleRoute($server);
        $router->get('/test', function ($srv) use (&$received_server) {
            $received_server = $srv;
        });

        $this->assertNotNull($received_server);
        $this->assertEquals($server, $received_server);
        $this->assertEquals('custom_value', $received_server['CUSTOM_KEY']);
        $this->assertEquals('example.com', $received_server['HTTP_HOST']);
    }

    /** @test */
    public function controller_receives_server_target(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/controller',
            'HTTP_HOST' => 'example.com',
            'SERVER_PORT' => '8080',
        ];

        $router = new HandleRoute($server);
        $router->get('/controller', [TestController::class, 'index']);

        $this->assertNotNull(TestController::$received_server);
        $this->assertEquals($server, TestController::$received_server);
        $this->assertEquals('example.com', TestController::$received_server['HTTP_HOST']);
        $this->assertEquals('8080', TestController::$received_server['SERVER_PORT']);
    }

    /** @test */
    public function closure_receives_all_server_array_keys(): void
    {
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/submit',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer token123',
            'REMOTE_ADDR' => '127.0.0.1',
        ];
        $received_server = null;

        $router = new HandleRoute($server);
        $router->post('/submit', function ($srv) use (&$received_server) {
            $received_server = $srv;
        });

        $this->assertArrayHasKey('REQUEST_METHOD', $received_server);
        $this->assertArrayHasKey('REQUEST_URI', $received_server);
        $this->assertArrayHasKey('CONTENT_TYPE', $received_server);
        $this->assertArrayHasKey('HTTP_AUTHORIZATION', $received_server);
        $this->assertArrayHasKey('REMOTE_ADDR', $received_server);
        $this->assertEquals('application/json', $received_server['CONTENT_TYPE']);
        $this->assertEquals('Bearer token123', $received_server['HTTP_AUTHORIZATION']);
    }

    /** @test */
    public function controller_receives_all_server_array_keys(): void
    {
        $server = [
            'REQUEST_METHOD' => 'DELETE',
            'REQUEST_URI' => '/resource/123',
            'HTTP_X_CUSTOM_HEADER' => 'custom_value',
            'QUERY_STRING' => 'force=true',
        ];

        $router = new HandleRoute($server);
        $router->delete('/resource/123', [TestController::class, 'destroy']);

        $this->assertArrayHasKey('REQUEST_METHOD', TestController::$received_server);
        $this->assertArrayHasKey('REQUEST_URI', TestController::$received_server);
        $this->assertArrayHasKey('HTTP_X_CUSTOM_HEADER', TestController::$received_server);
        $this->assertArrayHasKey('QUERY_STRING', TestController::$received_server);
        $this->assertEquals('DELETE', TestController::$received_server['REQUEST_METHOD']);
        $this->assertEquals('custom_value', TestController::$received_server['HTTP_X_CUSTOM_HEADER']);
    }

    /** @test */
    public function server_target_is_passed_by_reference_to_closure(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/test',
        ];

        $router = new HandleRoute($server);
        $router->get('/test', function (&$srv) {
            $srv['MODIFIED'] = 'true';
        });

        $this->assertEquals('true', $server['MODIFIED']);
    }

    /** @test */
    public function multiple_routes_each_receive_server_target(): void
    {
        $server = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/second',
            'CUSTOM_KEY' => 'value',
        ];
        $first_received = null;
        $second_received = null;

        $router = new HandleRoute($server);
        $router
            ->get('/first', function ($srv) use (&$first_received) {
                $first_received = $srv;
            })
            ->get('/second', function ($srv) use (&$second_received) {
                $second_received = $srv;
            });

        $this->assertNull($first_received);
        $this->assertNotNull($second_received);
        $this->assertEquals('value', $second_received['CUSTOM_KEY']);
    }
}
