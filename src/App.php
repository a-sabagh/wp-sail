<?php

namespace WPSail;

use DI\Container;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use WPSail\Providers\HttpServiceProvider;
use WPSail\Providers\TranslationServiceProvider;
use WPSail\Support\ServiceProvider;

class App
{
    protected Container $container_instance;

    public function __construct(?Container $container = null)
    {
        $this->container_instance = $container ?? new Container();

        $this->container_instance->set(self::class, $this);
        $this->container_instance->set(Container::class, $this->container_instance);
        $this->container_instance->set(ContainerInterface::class, $this->container_instance);

        add_action('plugins_loaded', [$this, 'register_providers']);
    }

    public function register_providers(): void
    {
        /**
         * Filter the service providers that make up the WP Sail application.
         *
         * @param array<int, class-string<ServiceProvider>> $providers
         */
        $providers = apply_filters('wpsail_service_providers', [
            TranslationServiceProvider::class,
            HttpServiceProvider::class,
        ]);

        if (!is_array($providers)) {
            throw new InvalidArgumentException('The wpsail_service_providers filter must return an array.');
        }

        foreach ($providers as $provider) {
            if (!is_string($provider) || !is_a($provider, ServiceProvider::class, true)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Service provider [%s] must extend %s.',
                        is_string($provider) ? $provider : get_debug_type($provider),
                        ServiceProvider::class,
                    ),
                );
            }

            (new $provider($this))->register();
        }
    }

    public function container(): Container
    {
        return $this->container_instance;
    }

    public function make(string $id): mixed
    {
        return $this->container_instance->get($id);
    }
}
