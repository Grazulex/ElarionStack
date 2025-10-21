<?php

declare(strict_types=1);

namespace Elarion\Tests\Support;

use Elarion\Http\Message\Response;
use Elarion\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Helper Functions
 *
 * Tests all global helper functions for correct behavior.
 */
final class HelpersTest extends TestCase
{
    // ========================================
    // ENV HELPER TESTS
    // ========================================

    public function test_env_returns_value_from_env_array(): void
    {
        $_ENV['TEST_VAR'] = 'test_value';

        $this->assertSame('test_value', env('TEST_VAR'));

        unset($_ENV['TEST_VAR']);
    }

    public function test_env_returns_value_from_server_array(): void
    {
        $_SERVER['TEST_SERVER_VAR'] = 'server_value';

        $this->assertSame('server_value', env('TEST_SERVER_VAR'));

        unset($_SERVER['TEST_SERVER_VAR']);
    }

    public function test_env_returns_default_when_not_found(): void
    {
        $this->assertSame('default', env('NON_EXISTENT_VAR', 'default'));
    }

    public function test_env_returns_null_as_default(): void
    {
        $this->assertNull(env('NON_EXISTENT_VAR'));
    }

    public function test_env_converts_true_strings_to_boolean(): void
    {
        $_ENV['BOOL_TRUE'] = 'true';

        $this->assertTrue(env('BOOL_TRUE'));

        unset($_ENV['BOOL_TRUE']);
    }

    public function test_env_converts_false_strings_to_boolean(): void
    {
        $_ENV['BOOL_FALSE'] = 'false';

        $this->assertFalse(env('BOOL_FALSE'));

        unset($_ENV['BOOL_FALSE']);
    }

    public function test_env_converts_null_string_to_null(): void
    {
        $_ENV['NULL_VAR'] = 'null';

        $this->assertNull(env('NULL_VAR'));

        unset($_ENV['NULL_VAR']);
    }

    public function test_env_converts_empty_string(): void
    {
        $_ENV['EMPTY_VAR'] = 'empty';

        $this->assertSame('', env('EMPTY_VAR'));

        unset($_ENV['EMPTY_VAR']);
    }

    // ========================================
    // CONFIG HELPER TESTS
    // ========================================

    public function test_config_returns_config_instance_when_no_key(): void
    {
        $result = config();

        $this->assertInstanceOf(\Elarion\Config\ConfigRepository::class, $result);
    }

    public function test_config_returns_default_for_nonexistent_key(): void
    {
        $this->assertSame('default', config('nonexistent.key', 'default'));
    }

    // ========================================
    // DUMP HELPER TESTS
    // Note: var_dump writes to stdout which is difficult to capture in tests
    // The dump() function is tested through actual usage
    // ========================================

    // ========================================
    // DD HELPER TESTS
    // Note: Cannot test dd() directly as it calls exit()
    // These would require process isolation
    // ========================================

    // ========================================
    // VALUE HELPER TESTS
    // ========================================

    public function test_value_returns_value_directly(): void
    {
        $this->assertSame('test', value('test'));
    }

    public function test_value_calls_closure(): void
    {
        $result = value(fn () => 'from_closure');

        $this->assertSame('from_closure', $result);
    }

    public function test_value_passes_arguments_to_closure(): void
    {
        $result = value(fn ($a, $b) => $a + $b, 5, 3);

        $this->assertSame(8, $result);
    }

    // ========================================
    // TAP HELPER TESTS
    // ========================================

    public function test_tap_returns_value(): void
    {
        $result = tap('value', fn ($v) => strtoupper($v));

        $this->assertSame('value', $result);
    }

    public function test_tap_calls_callback(): void
    {
        $called = false;

        tap('value', function ($v) use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
    }

    public function test_tap_without_callback_returns_value(): void
    {
        $result = tap('value');

        $this->assertSame('value', $result);
    }

    // ========================================
    // WITH HELPER TESTS
    // ========================================

    public function test_with_returns_value_without_callback(): void
    {
        $result = with('value');

        $this->assertSame('value', $result);
    }

    public function test_with_calls_callback_and_returns_result(): void
    {
        $result = with('value', fn ($v) => strtoupper($v));

        $this->assertSame('VALUE', $result);
    }

    // ========================================
    // RESPONSE HELPER TESTS
    // ========================================

    public function test_response_creates_response_with_string(): void
    {
        $response = response('Hello World');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello World', (string) $response->getBody());
    }

    public function test_response_creates_response_with_status(): void
    {
        $response = response('Not Found', 404);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_response_creates_json_response_for_array(): void
    {
        $data = ['key' => 'value'];
        $response = response($data);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertSame($data, $body);
    }

    public function test_response_creates_json_response_for_object(): void
    {
        $data = (object) ['key' => 'value'];
        $response = response($data);

        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function test_response_accepts_custom_headers(): void
    {
        $response = response('test', 200, ['X-Custom' => 'Header']);

        $this->assertSame('Header', $response->getHeaderLine('X-Custom'));
    }

    public function test_response_with_empty_data(): void
    {
        $response = response();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
    }

    // ========================================
    // COLLECT HELPER TESTS
    // ========================================

    public function test_collect_creates_collection_from_array(): void
    {
        $collection = collect([1, 2, 3]);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame([1, 2, 3], $collection->toArray());
    }

    public function test_collect_creates_empty_collection(): void
    {
        $collection = collect();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(0, $collection);
    }

    public function test_collect_works_with_iterable(): void
    {
        $generator = function () {
            yield 1;
            yield 2;
            yield 3;
        };

        $collection = collect($generator());

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertSame([1, 2, 3], $collection->toArray());
    }

    public function test_collect_is_chainable(): void
    {
        $result = collect([1, 2, 3, 4, 5])
            ->filter(fn ($n) => $n > 2)
            ->map(fn ($n) => $n * 2)
            ->toArray();

        $this->assertSame([6, 8, 10], array_values($result));
    }

    // ========================================
    // ROUTE HELPER TESTS
    // ========================================

    public function test_route_returns_router_instance_when_no_name(): void
    {
        $router = route();

        $this->assertInstanceOf(\Elarion\Routing\Router::class, $router);
    }

    public function test_route_returns_same_instance(): void
    {
        // The route() helper uses a static router instance
        $router1 = route();
        $router2 = route();

        $this->assertSame($router1, $router2);
    }
}
