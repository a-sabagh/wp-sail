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

    public function test_view_response_renders_a_template_with_data_and_status(): void
    {
        $response = new ViewResponse(
            dirname(__DIR__) . '/Fixtures/views/product.php',
            201,
            data: [
                'product_id' => 42,
                'title' => 'Created',
            ],
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
        $this->assertSame(
            "<article data-product-id=\"42\">\n    <h1>Created</h1>\n</article>\n",
            $response->getContent(),
        );
    }

    public function test_view_response_rejects_a_missing_template(): void
    {
        $template = dirname(__DIR__) . '/Fixtures/views/missing.php';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("View template [{$template}] must be a readable file.");

        new ViewResponse($template);
    }

    public function test_response_rejects_an_invalid_http_status(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new JsonResponse([], 700);
    }
}
