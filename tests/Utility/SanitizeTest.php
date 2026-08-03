<?php

namespace WPSail\Tests\Utility;

use Exception;
use PHPUnit\Framework\TestCase;
use WPSail\Utility\Sanitize;

final class SanitizeTest extends TestCase
{
    public function test_make_sanitizes_mapped_values_and_applies_field_filters(): void
    {
        $data = [
            'name' => '<b>Sail</b>',
            'price' => '1,250',
            'untouched' => 'original',
        ];
        $uppercase = static fn($value) => strtoupper($value);
        add_filter('wpsail_sanitize_type_name_string', $uppercase);

        try {
            $result = Sanitize::make($data, [
                'name' => 'string',
                'price' => 'price-format',
                'missing' => 'integer',
            ]);
        } finally {
            remove_filter('wpsail_sanitize_type_name_string', $uppercase);
        }

        $this->assertSame([
            'name' => 'SAIL',
            'price' => 1250,
            'untouched' => 'original',
        ], $result);
        $this->assertSame($result, $data);
    }

    public function test_make_returns_early_for_empty_input(): void
    {
        $data = [];

        $this->assertSame([], Sanitize::make($data, ['name' => 'string']));

        $data = ['name' => '<b>Sail</b>'];
        $this->assertSame($data, Sanitize::make($data, []));
    }

    public function test_make_rejects_an_undefined_sanitizer(): void
    {
        $data = ['name' => 'Sail'];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('sanitize type missing is undefined');

        Sanitize::make($data, ['name' => 'missing']);
    }

    public function test_string_sanitizes_text(): void
    {
        $this->assertSame('Hello world', Sanitize::string('<b>Hello</b> world'));
    }

    public function test_integer_converts_formatted_numbers(): void
    {
        $this->assertSame(1234567, Sanitize::integer('1,234,567'));
        $this->assertSame(-42, Sanitize::integer('-42'));
    }

    public function test_boolean_converts_values_using_php_boolean_semantics(): void
    {
        $this->assertTrue(Sanitize::boolean(1));
        $this->assertTrue(Sanitize::boolean('yes'));
        $this->assertFalse(Sanitize::boolean(0));
        $this->assertFalse(Sanitize::boolean(''));
    }

    public function test_price_format_converts_prices_and_preserves_empty_values_as_null(): void
    {
        $this->assertSame(1250, Sanitize::price_format('1,250'));
        $this->assertNull(Sanitize::price_format(''));
    }

    public function test_array_filter_removes_empty_values_and_preserves_keys(): void
    {
        $value = ['first' => 'keep', 'zero' => 0, 'empty' => '', 'second' => 2];

        $this->assertSame(
            ['first' => 'keep', 'second' => 2],
            Sanitize::array_filter($value),
        );
    }

    public function test_array_integer_normalizes_input_and_converts_every_value(): void
    {
        $this->assertSame([12, 3, 0], Sanitize::array_integer(['12', '3.9', null]));
        $this->assertSame([42], Sanitize::array_integer('42'));
    }

    public function test_email_sanitizes_an_email_address(): void
    {
        $this->assertSame(
            'first.last+tag@example.com',
            Sanitize::email('first.last+tag @example.com'),
        );
    }

    public function test_untrailingslashit_removes_trailing_slashes(): void
    {
        $this->assertSame(
            'https://example.com/path',
            Sanitize::untrailingslashit('https://example.com/path///\\'),
        );
    }

    public function test_trailingslashit_adds_exactly_one_trailing_slash(): void
    {
        $this->assertSame(
            'https://example.com/path/',
            Sanitize::trailingslashit('https://example.com/path///'),
        );
    }

    public function test_explode_eol_splits_platform_line_endings(): void
    {
        $value = 'first' . PHP_EOL . 'second' . PHP_EOL . 'third';

        $this->assertSame(['first', 'second', 'third'], Sanitize::explode_eol($value));
    }

    public function test_stripslashes_removes_backslashes(): void
    {
        $this->assertSame("O'Reilly", Sanitize::stripslashes("O\\'Reilly"));
    }

    public function test_slug_sanitizes_a_url_friendly_slug(): void
    {
        $this->assertSame('hello-sail-world', Sanitize::slug('Hello, Sail World!'));
    }

    public function test_title_sanitizes_a_title_using_wordpress_defaults(): void
    {
        $this->assertSame('hello-sail', Sanitize::title('Héllo Sail'));
    }

    public function test_none_returns_the_original_value(): void
    {
        $value = new \stdClass();

        $this->assertSame($value, Sanitize::none($value));
    }
}
