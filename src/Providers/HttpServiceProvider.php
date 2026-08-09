<?php

namespace WPSail\Providers;

use WPSail\Http\Kernel as HTTPKernel;
use WPSail\Support\ServiceProvider;

use function DI\factory;

class HttpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $http_kernel = factory(fn(): HTTPKernel => new HTTPKernel($this->container()));

        $this->container()->set(HTTPKernel::class, $http_kernel);

        add_action('init', [$this, 'boot_kernel']);
    }

    public function boot_kernel(): HTTPKernel
    {
        return $this->app->make(HTTPKernel::class);
    }
}
