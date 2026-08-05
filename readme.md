# WP Sail

## Controller requests

WP Sail captures the current HTTP request and injects it into controller actions through PHP-DI. Type-hint `WPSail\Http\Request` on the action; controllers do not need to read PHP superglobals directly.

```php
<?php

namespace Acme\Plugin\Http\Controllers;

use WPSail\Http\Request;
use WPSail\Http\Response\JsonResponse;

class ProductController
{
    public function show(Request $request): JsonResponse
    {
        $id = $request->query->get('id');

        return new JsonResponse([
            'id' => $id,
        ]);
    }
}
```

For a route such as `/api/product/show?id=42`, `$request->query->get('id')` returns `42`. WP Sail removes the slashes WordPress adds to request globals before injecting the request.

The request extends Symfony's HttpFoundation `Request`. Use its input bags and methods according to the source of the data:

```php
// URL query string: ?page=2
$page = $request->query->getInt('page', 1);

// Form-encoded POST data
$name = $request->request->get('name');

// Form data or a JSON request body
$email = $request->getPayload()->get('email');

// Headers, cookies, and uploaded files
$token = $request->headers->get('Authorization');
$session = $request->cookies->get('session');
$upload = $request->files->get('document');

// Request information and raw body
$method = $request->getMethod();
$uri = $request->getUri();
$content = $request->getContent();
```

Routing metadata is available through request attributes:

```php
$endpoint = $request->attributes->get('_endpoint');
$controller = $request->attributes->get('_controller');
$action = $request->attributes->get('_action');
$route = $request->attributes->get('_route');
```

Other dependencies may be injected into the same action. PHP-DI resolves them normally:

```php
public function show(Request $request, ProductRepository $products): JsonResponse
{
    $product = $products->find($request->query->get('id'));

    return new JsonResponse($product);
}
```

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
    public function index(): JsonResponse
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

use WPSail\Http\Request;
use WPSail\Http\Response\ViewResponse;

class PageController
{
    public function show(Request $request): ViewResponse
    {
        $title = $request->query->get('title', __('Page', 'acme-plugin'));
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
