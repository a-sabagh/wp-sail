<?php

namespace WPSail\Tests\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WPSail\Http\Response\JsonResponse;
use WPSail\Http\Response\ViewResponse;

final class ResponseTest extends TestCase
{
    public function test_json_response_encodes_data_and_status(): void
    {
        $response = new JsonResponse(['created' => true], 201);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame(['created' => true], json_decode($response->getContent(), true));
    }

    public function test_view_response_contains_html_content_and_status(): void
    {
        $response = new ViewResponse('<h1>Created</h1>', 201);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame('<h1>Created</h1>', $response->getContent());
    }

    public function test_response_rejects_an_invalid_http_status(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new JsonResponse([], 700);
    }
}
