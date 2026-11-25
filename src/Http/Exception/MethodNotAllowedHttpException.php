<?php

namespace WPSail\Http\Exception;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use WPSail\Http\Request;

/**
 * @link https://github.com/symfony/http-kernel/blob/8.2/Exception/MethodNotAllowedHttpException.php 
 */
class MethodNotAllowedHttpException extends RuntimeException
{
    public function __construct(
        public Request $request,
        ?Throwable $previous = null,
    ) {
        $attributes = $request->attributes;

        parent::__construct(
            sprintf(
                __('Method not allowed "%1$s/%2$s/%3$s".', 'wp-sail'),
                $attributes->get('_endpoint'),
                $attributes->get('_controller'),
                $attributes->get('_action'),
            ),
            Response::HTTP_METHOD_NOT_ALLOWED,
            $previous,
        );
    }
}
