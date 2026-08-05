<?php

namespace WPSail\Workbench\Http;

use WPSail\Http\Request;
use WPSail\Http\Response\JsonResponse;
use WPSail\Workbench\Services\FakeService;

final class FakeController
{
    public function __construct(
        public readonly FakeService $service,
    ) {}

    /**
     * @see \WPSail\Tests\Http\KernelDependencyInjectionTest
     */
    public function show(Request $request): JsonResponse
    {
        return new JsonResponse(
            $this->service->retrieve($request->query->get('id', 'missing')),
        );
    }
}
