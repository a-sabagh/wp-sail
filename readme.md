# WP Sail

## Index

- [Controller requests](#controller-requests)
- [Flash data after a redirect](#flash-data-after-a-redirect)
- [Controller responses](#controller-responses)
  - [Redirect responses](#redirect-responses)
  - [JSON responses](#json-responses)
  - [View responses](#view-responses)

## Controller requests

WP Sail captures the current HTTP request and injects it into controller actions through [PHP-DI](https://github.com/PHP-DI/PHP-DI). Type-hint `WPSail\Http\Request` on the action; controllers do not need to read PHP superglobals directly.

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

## Flash data after a redirect

Routed requests have a lazy Symfony session. The session starts only when it is accessed, and flash data remains available for the next request only. This supports the usual POST/Redirect/GET flow without keeping validation state in query parameters.

Store validation errors and the non-sensitive input before returning Symfony's `RedirectResponse`:

```php
use Symfony\Component\HttpFoundation\RedirectResponse;
use WPSail\Http\Request;
use WPSail\Utility\Arr;

public function update(Request $request): RedirectResponse
{
    $errors = [
        'email' => [__('The email field is required.', 'acme-plugin')],
    ];

    $flash = $request->getSession()->getFlashBag();
    $flash->set('wpsail.validation_errors', $errors);
    $flash->set(
        'wpsail.old_input',
        Arr::except($request->request->all(), [
            'password',
            'password_confirmation',
            '_wpnonce',
        ]),
    );

    return new RedirectResponse(home_url('/account/profile'));
}
```

Retrieve the data in the redirected GET action:

```php
$flash = $request->getSession()->getFlashBag();

$errors = $flash->get('wpsail.validation_errors');
$old = $flash->get('wpsail.old_input');
```

`get()` returns an empty array when the key is absent and consumes the stored value. Always exclude passwords, nonces, tokens, and other secrets from old input, and escape messages and values when rendering them.

## Controller responses

Every routed controller action must return a Symfony HttpFoundation response object. Common response types are:

- `Symfony\Component\HttpFoundation\RedirectResponse` for redirects.
- `WPSail\Http\Response\JsonResponse` for JSON responses.
- `WPSail\Http\Response\ViewResponse` for HTML responses.

The returned response object determines how the kernel handles the result. Route definitions no longer need a `type` value. Returning a plain array, string, or other value is invalid and will be converted into an error response by the kernel.

### Redirect responses

Return Symfony's `RedirectResponse` to send the client to another URL:

```php
use Symfony\Component\HttpFoundation\RedirectResponse;

public function store(): RedirectResponse
{
    // Save the submitted data.

    return new RedirectResponse(
        home_url('/products'),
        RedirectResponse::HTTP_SEE_OTHER,
    );
}
```

The default status is `302 Found`. Use `303 See Other` after a successful form submission when the redirected request should use `GET`. Build redirects from trusted URLs; see [Flash data after a redirect](#flash-data-after-a-redirect) for carrying validation errors and old input.

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
