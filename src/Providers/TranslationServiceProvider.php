<?php

namespace WPSail\Providers;

use WPSail\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        add_action('init', [$this, 'load_textdomain']);
    }

    public function load_textdomain(): bool
    {
        $plugin_rel_path = basename(dirname(__DIR__, 2)) . '/languages';

        return load_plugin_textdomain(
            domain: 'wp-sail', 
            deprecated: false, 
            plugin_rel_path: $plugin_rel_path
        );
    }
}
