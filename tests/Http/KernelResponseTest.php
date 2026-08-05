<?php

namespace WPSail\Tests\Http;

use DI\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;
use WPSail\Http\Kernel;
use WPSail\Http\Request;
use WPSail\Http\Response\JsonResponse;

final class KernelResponseTest extends TestCase
{
    private TestKernel $kernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernel = new TestKernel(new Container());
    }

    protected function tearDown(): void
    {
        remove_action('template_redirect', [$this->kernel, 'handle']);
        remove_action('shutdown', [$this->kernel, 'handle_shutdown']);
        unset($GLOBALS['endpoint']);

        parent::tearDown();
    }

    public function test_controller_response_object_determines_the_response_type(): void
    {
        $expected = new JsonResponse(['ok' => true]);
        $controller = new class ($expected) {
            public function __construct(private Response $response) {}

            public function index(): Response
            {
                return $this->response;
            }
        };

        $this->assertSame($expected, $this->kernel->dispatch($controller, 'index', new Request()));
    }

    public function test_controller_must_return_an_http_foundation_response(): void
    {
        $controller = new class {
            public function index(): array
            {
                return ['ok' => true];
            }
        };

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'must return Symfony\Component\HttpFoundation\Response; array returned.',
        );

        $this->kernel->dispatch($controller, 'index', new Request());
    }

    public function test_kernel_registers_a_wordpress_shutdown_callback(): void
    {
        $this->assertSame(10, has_action('shutdown', [$this->kernel, 'handle_shutdown']));
    }

    public function test_shutdown_action_runs_only_for_a_routed_request(): void
    {
        $calls = 0;
        $callback = static function () use (&$calls): void {
            ++$calls;
        };

        add_action('wpsail_request_shutdown', $callback);

        try {
            unset($GLOBALS['endpoint']);
            $this->kernel->handle_shutdown();

            $GLOBALS['endpoint'] = 'api';
            $this->kernel->handle_shutdown();
        } finally {
            remove_action('wpsail_request_shutdown', $callback);
        }

        $this->assertSame(1, $calls);
    }
}

final class TestKernel extends Kernel
{
    public function dispatch(object $controller, string $action, Request $request): Response
    {
        return $this->dispatch_response($controller, $action, $request);
    }
}
