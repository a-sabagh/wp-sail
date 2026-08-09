<?php

namespace WPSail\Tests\Providers;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use WPSail\App;
use WPSail\Http\Kernel;
use WPSail\Providers\HttpServiceProvider;
use WPSail\Providers\TranslationServiceProvider;
use WPSail\Support\ServiceProvider;

final class CoreServiceProviderTest extends TestCase
{
    /** @var array<int, App> */
    private array $applications = [];

    /** @var array<int, HttpServiceProvider> */
    private array $http_providers = [];

    /** @var array<int, TranslationServiceProvider> */
    private array $translation_providers = [];

    /** @var array<int, Kernel> */
    private array $kernels = [];

    protected function tearDown(): void
    {
        foreach ($this->applications as $app) {
            remove_action('plugins_loaded', [$app, 'register_providers']);
        }

        foreach ($this->http_providers as $provider) {
            remove_action('init', [$provider, 'boot_kernel']);
        }

        foreach ($this->translation_providers as $provider) {
            remove_action('init', [$provider, 'load_textdomain']);
        }

        foreach ($this->kernels as $kernel) {
            remove_action('template_redirect', [$kernel, 'handle']);
            remove_action('shutdown', [$kernel, 'handle_shutdown']);
        }

        unset($GLOBALS['wpsail_loaded_textdomains']);
        remove_all_filters('wpsail_service_providers');

        parent::tearDown();
    }

    public function test_core_providers_attach_their_init_hooks_during_registration(): void
    {
        $app = $this->make_application();
        $http = $this->make_http_provider($app);
        $translation = $this->make_translation_provider($app);

        $this->assertSame(10, has_action('init', [$http, 'boot_kernel']));
        $this->assertSame(10, has_action('init', [$translation, 'load_textdomain']));
    }

    public function test_http_kernel_is_a_singleton_using_the_application_container(): void
    {
        $app = $this->make_application();
        $provider = $this->make_http_provider($app);

        $first = $this->boot_kernel($provider);
        $second = $this->boot_kernel($provider);
        $container = (new ReflectionProperty(Kernel::class, 'container'))->getValue($first);

        $this->assertSame($first, $second);
        $this->assertSame($app->container(), $container);
    }

    public function test_provider_bindings_are_used_when_the_kernel_resolves_a_controller(): void
    {
        $app = $this->make_application();
        (new RepositoryServiceProvider($app))->register();
        $kernel = $this->boot_kernel($this->make_http_provider($app));

        $controller = (new ReflectionMethod(Kernel::class, 'resolve_controller'))->invoke(
            $kernel,
            __NAMESPACE__,
            'test',
            'ProviderBoundController',
            'index',
        );

        $this->assertInstanceOf(ProviderBoundController::class, $controller);
        $this->assertSame($app->make(ProductRepository::class), $controller->repository);
    }

    public function test_translation_provider_loads_the_wp_sail_textdomain(): void
    {
        $app = $this->make_application();
        $provider = $this->make_translation_provider($app);

        $this->assertTrue($provider->load_textdomain());
        $this->assertSame(
            [['wp-sail', false, 'wp-sail/languages']],
            $GLOBALS['wpsail_loaded_textdomains'],
        );
    }

    private function make_application(): App
    {
        $app = new App();
        $this->applications[] = $app;

        return $app;
    }

    private function make_http_provider(App $app): HttpServiceProvider
    {
        $provider = new HttpServiceProvider($app);
        $provider->register();
        $this->http_providers[] = $provider;

        return $provider;
    }

    private function make_translation_provider(App $app): TranslationServiceProvider
    {
        $provider = new TranslationServiceProvider($app);
        $provider->register();
        $this->translation_providers[] = $provider;

        return $provider;
    }

    private function boot_kernel(HttpServiceProvider $provider): Kernel
    {
        $kernel = $provider->boot_kernel();
        $this->kernels[] = $kernel;

        return $kernel;
    }
}

interface ProductRepository {}

final class WordPressProductRepository implements ProductRepository {}

final class ProviderBoundController
{
    public function __construct(
        public ProductRepository $repository,
    ) {}

    public function index(): void {}
}

final class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container()->set(ProductRepository::class, new WordPressProductRepository());
    }
}
