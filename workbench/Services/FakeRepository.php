<?php

namespace WPSail\Workbench\Services;

final class FakeRepository
{
    public function find(string $identifier): array
    {
        return [
            'id' => $identifier,
            'source' => 'fake-repository',
        ];
    }
}
