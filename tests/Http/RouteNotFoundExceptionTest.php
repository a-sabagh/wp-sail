<?php

namespace WPSail\Tests\Http;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\TestCase;
use WPSail\Http\Exception\RouteNotFoundException;

final class RouteNotFoundExceptionTest extends TestCase
{
    public function test_exception_describes_the_unresolved_route(): void
    {
        $previous = new RuntimeException('Controller service was not found.');
        $exception = new RouteNotFoundException(
            'api',
            'ProductController',
            'show',
            $previous,
        );

        $this->assertSame(Response::HTTP_NOT_FOUND, $exception->getCode());
        $this->assertSame('api', $exception->endpoint);
        $this->assertSame('ProductController', $exception->controller);
        $this->assertSame('show', $exception->action);
        $this->assertSame('No route matches "api/ProductController/show".', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
