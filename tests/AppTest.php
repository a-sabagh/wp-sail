<?php

namespace WPSail\Tests;

use DI\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use WPSail\App;
use WPSail\Support\ServiceProvider;

final class AppTest extends TestCase
{
    private TestApp $app;

    protected function setUp(): void
    {
        parent::setUp();

        ProviderRecorder::reset();
        $this->app = new TestApp();
    }

    protected function tearDown(): void
    {
        remove_action('plugins_loaded', [$this->app, 'register_providers']);
        remove_all_filters('wpsail_service_providers');

        parent::tearDown();
    }

    public function test_application_registers_itself_and_the_shared_container(): void
    {
        $container = $this->app->container();

        $this->assertSame($this->app, $container->get(App::class));
        $this->assertSame($container, $container->get(Container::class));
        $this->assertSame($container, $container->get(ContainerInterface::class));
    }

    public function test_application_registers_each_configured_provider(): void
    {
        add_filter('wpsail_service_providers', static fn(array $providers): array => [
            ...$providers,
            FirstProvider::class,
            SecondProvider::class,
        ]);

        $this->app->register_providers();

        $this->assertSame(['first.register', 'second.register'], ProviderRecorder::$events);
    }

    public function test_provider_must_extend_the_base_service_provider(): void
    {
        add_filter(
            'wpsail_service_providers',
            static fn(array $providers): array => [...$providers, \stdClass::class],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must extend WPSail\Support\ServiceProvider');

        $this->app->register_providers();
    }

    public function test_provider_filter_must_return_an_array(): void
    {
        add_filter('wpsail_service_providers', static fn(): string => 'invalid');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must return an array');

        $this->app->register_providers();
    }
}

final class TestApp extends App
{
    protected function core_providers(): array
    {
        return [];
    }
}

final class ProviderRecorder
{
    /** @var array<int, string> */
    public static array $events = [];

    public static function reset(): void
    {
        self::$events = [];
    }
}

final class FirstProvider extends ServiceProvider
{
    public function register(): void
    {
        ProviderRecorder::$events[] = 'first.register';
    }
}

final class SecondProvider extends ServiceProvider
{
    public function register(): void
    {
        ProviderRecorder::$events[] = 'second.register';
    }
}
