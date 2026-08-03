<?php

namespace WPSail\Workbench\Http;

use WPSail\Http\Response\JsonResponse;
use WPSail\Workbench\Services\FakeService;

final class FakeController
{
    public function __construct(
        public readonly FakeService $service,
    ) {}

    public function show(array $parameters): JsonResponse
    {
        return new JsonResponse(
            $this->service->retrieve($parameters['id'] ?? 'missing'),
        );
    }
}
