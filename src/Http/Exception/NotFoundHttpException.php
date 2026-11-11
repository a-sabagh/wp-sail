<?php

namespace WPSail\Http\Exception;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class NotFoundHttpException extends RuntimeException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            $message,
            Response::HTTP_NOT_FOUND,
            $previous,
        );
    }
}
