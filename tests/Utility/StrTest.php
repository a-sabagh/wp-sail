<?php

namespace WPSail\Tests\Utility;

use PHPUnit\Framework\TestCase;
use WPSail\Utility\Str;

final class StrTest extends TestCase {

    public function test_lower_converts_unicode_text_to_lowercase(): void {
        $this->assertSame('ärger', Str::lower('ÄRGER'));
    }

    public function test_snake_converts_camel_case_with_a_configurable_delimiter(): void {
        $this->assertSame('camel_case_value', Str::snake('camelCaseValue'));
        $this->assertSame('camel-case-value', Str::snake('camelCaseValue', '-'));
    }
}
