<?php

use WPSail\Utility\Arr;

if (! function_exists('data_get')) {
    /**
     * Get an item from an array using dot notation.
     *
     * @param  mixed  $target
     * @param  string|int|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        return Arr::get($target, $key, $default);
    }
}

/**
 * Get the permalink for a WP Sail route.
 *
 * @param  string  $endpoint
 * @param  string  $class
 * @param  string  $action
 * @return string
 */
function wpsail_get_permalink($endpoint, $class, $action)
{
    return home_url("{$endpoint}/{$class}/{$action}");
}

function wpsail_get_string_nullable($value)
{
    return 0 === strlen($value) ? $value : null;
}

function wpsail_check_string_nullable($value)
{
    return 0 === strlen($value);
}
