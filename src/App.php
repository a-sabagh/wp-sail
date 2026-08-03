<?php

namespace WPSail;

use DI\Container;
use WPSail\Http\Kernel as HTTPKernel;

class App
{
    public function __construct()
    {
        add_action('init', [$this, 'load_textdomain']);
        add_action('init', [$this, 'boot_http_kernel']);
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('wp-sail', false, basename(dirname(__DIR__)) . '/languages');
    }

    /**
     * Boot the HTTP kernel.
     *
     * @return void
     */
    public function boot_http_kernel()
    {
        global $http_kernel;

        if (isset($http_kernel) && $http_kernel instanceof HTTPKernel) {
            return;
        }

        $http_kernel = new HTTPKernel(
            new Container(),
        );
    }
}
