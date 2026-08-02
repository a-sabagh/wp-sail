<?php

require_once __DIR__ . '/../vendor/autoload.php';

$wordpress_path = dirname(__DIR__, 4) . '/';

require_once $wordpress_path . 'wp-includes/plugin.php';
require_once $wordpress_path . 'wp-includes/compat.php';
require_once $wordpress_path . 'wp-includes/utf8.php';

if (! function_exists('is_utf8_charset')) {
    function is_utf8_charset($blog_charset = null) {
        return $blog_charset === null || in_array(strtolower($blog_charset), ['utf8', 'utf-8'], true);
    }
}

if (! function_exists('mbstring_binary_safe_encoding')) {
    function mbstring_binary_safe_encoding($reset = false) {
    }
}

if (! function_exists('reset_mbstring_encoding')) {
    function reset_mbstring_encoding() {
    }
}

if (! function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (! function_exists('get_locale')) {
    function get_locale() {
        return 'en_US';
    }
}

require_once $wordpress_path . 'wp-includes/formatting.php';
require_once __DIR__ . '/../src/functions.php';

add_filter('sanitize_title', 'sanitize_title_with_dashes', 10, 3);
