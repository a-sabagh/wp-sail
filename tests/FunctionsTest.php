<?php

namespace WPSail\Tests;

use PHPUnit\Framework\TestCase;

final class FunctionsTest extends TestCase
{
    public function test_get_permalink_returns_the_url_for_a_route(): void
    {
        $this->assertSame(
            'https://example.com/WCMApi/Payment/capture',
            wpsail_get_permalink('WCMApi', 'Payment', 'capture'),
        );
    }
}
