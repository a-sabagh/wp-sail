<?php

namespace WPSail\Support;

use DI\Container;
use WPSail\App;

abstract class ServiceProvider
{
    public function __construct(
        protected App $app,
    ) {}

    /**
     * Register services with the application container.
     */
    abstract public function register(): void;

    /**
     * Get the application instance.
     */
    public function app(): App
    {
        return $this->app;
    }

    /**
     * Get the shared service container.
     */
    public function container(): Container
    {
        return $this->app->container();
    }
}
