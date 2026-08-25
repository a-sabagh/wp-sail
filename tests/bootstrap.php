<?php

require_once __DIR__ . '/../vendor/autoload.php';

$wordpress_path = dirname(__DIR__, 4) . '/';

require_once $wordpress_path . 'wp-includes/plugin.php';
require_once $wordpress_path . 'wp-includes/compat.php';
require_once $wordpress_path . 'wp-includes/utf8.php';

if (! function_exists('is_utf8_charset')) {
    /**
     * Determine whether the given blog charset is UTF-8.
     *
     * @param  string|null  $blog_charset
     * @return bool
     */
    function is_utf8_charset($blog_charset = null)
    {
        return $blog_charset === null || in_array(strtolower($blog_charset), ['utf8', 'utf-8'], true);
    }
}

if (! function_exists('mbstring_binary_safe_encoding')) {
    /**
     * Set the mbstring internal encoding to a binary-safe encoding.
     *
     * @param  bool  $reset
     * @return void
     */
    function mbstring_binary_safe_encoding($reset = false) {}
}

if (! function_exists('reset_mbstring_encoding')) {
    /**
     * Reset the mbstring internal encoding to its previous value.
     *
     * @return void
     */
    function reset_mbstring_encoding() {}
}

if (! function_exists('__')) {
    /**
     * Retrieve the translated text for the given text domain.
     *
     * @param  string  $text
     * @param  string  $domain
     * @return string
     */
    function __($text, $domain = 'default')
    {
        return $text;
    }
}

if (! function_exists('get_locale')) {
    /**
     * Retrieve the current locale.
     *
     * @return string
     */
    function get_locale()
    {
        return 'en_US';
    }
}

if (! function_exists('load_plugin_textdomain')) {
    /**
     * Load a plugin's translated strings.
     *
     * @param  string        $domain
     * @param  string|false  $deprecated
     * @param  string|false  $plugin_rel_path
     * @return bool
     */
    function load_plugin_textdomain($domain, $deprecated = false, $plugin_rel_path = false)
    {
        $GLOBALS['wpsail_loaded_textdomains'][] = [$domain, $deprecated, $plugin_rel_path];

        return true;
    }
}

if (! function_exists('home_url')) {
    /**
     * Retrieve the home URL with an optional relative path.
     *
     * @param  string  $path
     * @return string
     */
    function home_url($path = '')
    {
        return 'https://example.com/' . ltrim($path, '/');
    }
}

require_once $wordpress_path . 'wp-includes/formatting.php';
require_once __DIR__ . '/../src/functions.php';

add_filter('sanitize_title', 'sanitize_title_with_dashes', 10, 3);
