<?php

namespace WPSail\Tests\Utility;

use PHPUnit\Framework\TestCase;
use WPSail\Utility\Arr;

final class ArrTest extends TestCase
{
    public function test_except_removes_requested_keys(): void
    {
        $array = [
            'name' => 'Sail',
            'password' => 'secret',
            'profile' => ['age' => 30, 'city' => 'Tehran'],
        ];

        $result = Arr::except($array, ['password', 'profile.age']);

        $this->assertSame([
            'name' => 'Sail',
            'profile' => ['city' => 'Tehran'],
        ], $result);
    }

    public function test_only_returns_requested_keys(): void
    {
        $array = ['name' => 'Sail', 'version' => 1, 'private' => true];

        $this->assertSame(
            ['name' => 'Sail', 'version' => 1],
            Arr::only($array, ['name', 'version']),
        );
    }

    public function test_accessible_only_accepts_arrays(): void
    {
        $this->assertTrue(Arr::accessible([]));
        $this->assertFalse(Arr::accessible('not-an-array'));
        $this->assertFalse(Arr::accessible(null));
    }

    public function test_exists_detects_present_keys_including_null_and_float_keys(): void
    {
        $array = ['name' => null, '1.5' => 'float key'];

        $this->assertTrue(Arr::exists($array, 'name'));
        $this->assertTrue(Arr::exists($array, 1.5));
        $this->assertFalse(Arr::exists($array, 'missing'));
    }

    public function test_set_assigns_nested_values_and_can_replace_the_array(): void
    {
        $array = [];

        Arr::set($array, 'profile.contact.email', 'sail@example.com');

        $this->assertSame([
            'profile' => [
                'contact' => ['email' => 'sail@example.com'],
            ],
        ], $array);

        Arr::set($array, null, ['replaced' => true]);
        $this->assertSame(['replaced' => true], $array);
    }

    public function test_get_reads_direct_and_nested_values_with_defaults(): void
    {
        $array = [
            'name' => 'Sail',
            'profile' => ['contact' => ['email' => 'sail@example.com']],
        ];

        $this->assertSame('Sail', Arr::get($array, 'name'));
        $this->assertSame('sail@example.com', Arr::get($array, 'profile.contact.email'));
        $this->assertSame('fallback', Arr::get($array, 'profile.phone', 'fallback'));
        $this->assertSame($array, Arr::get($array, null));
        $this->assertSame('fallback', Arr::get('invalid', 'name', 'fallback'));
    }

    public function test_add_only_assigns_values_that_are_missing_or_null(): void
    {
        $array = ['name' => 'Sail', 'description' => null];

        $array = Arr::add($array, 'name', 'Changed');
        $array = Arr::add($array, 'description', 'A plugin');
        $array = Arr::add($array, 'meta.version', 1);

        $this->assertSame([
            'name' => 'Sail',
            'description' => 'A plugin',
            'meta' => ['version' => 1],
        ], $array);
    }

    public function test_pull_returns_and_removes_a_nested_value(): void
    {
        $array = ['profile' => ['email' => 'sail@example.com', 'age' => 30]];

        $value = Arr::pull($array, 'profile.email');

        $this->assertSame('sail@example.com', $value);
        $this->assertSame(['profile' => ['age' => 30]], $array);
        $this->assertSame('fallback', Arr::pull($array, 'missing', 'fallback'));
    }

    public function test_forget_removes_multiple_direct_and_nested_keys(): void
    {
        $array = [
            'name' => 'Sail',
            'secret' => 'hidden',
            'profile' => ['email' => 'sail@example.com', 'age' => 30],
        ];

        Arr::forget($array, ['secret', 'profile.email', 'missing.path']);

        $this->assertSame([
            'name' => 'Sail',
            'profile' => ['age' => 30],
        ], $array);

        Arr::forget($array, []);
        $this->assertSame(['name' => 'Sail', 'profile' => ['age' => 30]], $array);
    }

    public function test_query_uses_rfc3986_encoding(): void
    {
        $query = Arr::query(['search' => 'sail boat', 'symbols' => 'a&b']);

        $this->assertSame('search=sail%20boat&symbols=a%26b', $query);
    }
}
