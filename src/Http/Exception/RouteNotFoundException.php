<?php

namespace WPSail\Http\Exception;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RouteNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $endpoint,
        public readonly string $controller,
        public readonly string $action,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                __('No route matches "%1$s/%2$s/%3$s".', 'wp-sail'),
                $endpoint,
                $controller,
                $action,
            ),
            Response::HTTP_NOT_FOUND,
            $previous,
        );
    }
}
