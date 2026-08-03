<?php

namespace WPSail\Workbench\Services;

final class FakeService
{
    public function __construct(
        public readonly FakeRepository $repository,
    ) {}

    public function retrieve(string $identifier): array
    {
        return $this->repository->find($identifier);
    }
}
