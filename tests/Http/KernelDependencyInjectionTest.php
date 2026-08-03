<?php

namespace WPSail\Tests\Http;

use DI\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use WPSail\Http\Kernel;
use WPSail\Http\Response\JsonResponse;
use WPSail\Workbench\Http\FakeController;
use WPSail\Workbench\Services\FakeRepository;
use WPSail\Workbench\Services\FakeService;

final class KernelDependencyInjectionTest extends TestCase
{
    private Container $container;
    private DependencyInjectionKernel $kernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        $this->kernel = new DependencyInjectionKernel($this->container);
    }

    protected function tearDown(): void
    {
        remove_action('template_redirect', [$this->kernel, 'handle']);
        remove_action('shutdown', [$this->kernel, 'handle_shutdown']);

        parent::tearDown();
    }

    public function test_kernel_resolves_a_controller_and_its_services_through_php_di(): void
    {
        $controller = $this->kernel->resolve(
            'WPSail\\Workbench\\Http',
            'workbench',
            'FakeController',
            'show',
        );

        $this->assertInstanceOf(FakeController::class, $controller);
        $this->assertSame($this->container->get(FakeService::class), $controller->service);
        $this->assertSame($this->container->get(FakeRepository::class), $controller->service->repository);

        $response = $this->kernel->dispatch($controller, 'show', ['id' => 'service-1']);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(
            [
                'id' => 'service-1',
                'source' => 'fake-repository',
            ],
            json_decode($response->getContent(), true),
        );
    }
}

final class DependencyInjectionKernel extends Kernel
{
    public function resolve(string $namespace, string $endpoint, string $controller, string $action): object
    {
        return $this->resolve_controller($namespace, $endpoint, $controller, $action);
    }

    public function dispatch(object $controller, string $action, array $parameters): Response
    {
        return $this->dispatch_response($controller, $action, $parameters);
    }
}
