# WP Sail

## Controller responses

Every routed controller action must return a Symfony HttpFoundation response object. WP Sail provides two response classes built on `symfony/http-foundation`:

- `WPSail\Http\Response\JsonResponse` for JSON responses.
- `WPSail\Http\Response\ViewResponse` for HTML responses.

The returned response object determines how the kernel handles the result. Route definitions no longer need a `type` value. Returning a plain array, string, or other value is invalid and will be converted into an error response by the kernel.

### JSON responses

Return `JsonResponse` from actions that provide structured API data:

```php
<?php

namespace Acme\Plugin\Http\Controllers;

use WPSail\Http\Response\JsonResponse;

class ProductController
{
    public function index(array $parameters): JsonResponse
    {
        return new JsonResponse(
            [
                'status' => true,
                'data' => [
                    'products' => [],
                ],
            ],
            JsonResponse::HTTP_OK,
        );
    }
}
```

The constructor accepts response data, an HTTP status code, and optional headers:

```php
return new JsonResponse(
    ['status' => true, 'data' => $product],
    JsonResponse::HTTP_CREATED,
    ['X-Resource-Type' => 'product'],
);
```

Symfony serializes the data as JSON and validates the HTTP status code. WordPress applies its native allowed-origin policy when the kernel sends the response.

### View responses

Return `ViewResponse` from actions that provide HTML. Escape dynamic values with the appropriate native WordPress escaping function:

```php
<?php

namespace Acme\Plugin\Http\Controllers;

use WPSail\Http\Response\ViewResponse;

class PageController
{
    public function show(array $parameters): ViewResponse
    {
        $title = $parameters['title'] ?? __('Page', 'acme-plugin');
        $content = sprintf('<h1>%s</h1>', esc_html($title));

        return new ViewResponse(
            $content,
            ViewResponse::HTTP_OK,
        );
    }
}
```

Custom response headers can be passed as the third constructor argument:

```php
return new ViewResponse(
    $html,
    ViewResponse::HTTP_OK,
    ['X-Frame-Options' => 'SAMEORIGIN'],
);
```

`ViewResponse` uses `text/html` with the WordPress site charset. Both response classes inherit the standard HttpFoundation response API, including methods such as `headers->set()`, `setStatusCode()`, and `setContent()`.
