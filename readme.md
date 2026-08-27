# WP Sail

WP Sail is a lightweight application layer for building structured, modern
WordPress plugins without fighting the platform beneath them. It brings
controller routing, dependency-injected requests, expressive responses, session
flash data, database utilities, and familiar helper APIs together in one focused
foundation—so your plugin code can stay clear, testable, and distinctly
WordPress-native.

WP Sail requires PHP 8.2 or later.

The plugin that uses WP Sail must declare it as a dependency in its main plugin
file. Add the `Requires Plugins` header alongside the plugin's other headers:

```php
<?php

/**
 * Plugin Name: WooCommerce Payment MultiStox
 * Description: Provides MultiStox payment functionality for WooCommerce.
 * Version: 0.1.0
 * Requires Plugins: wp-sail
 */
```

Use the WP Sail plugin directory slug, `wp-sail`, rather than its display name.
For multiple dependencies, provide a comma-separated list, for example
`Requires Plugins: woocommerce, wp-sail`. WordPress uses this header to prevent
the dependent plugin from being activated while WP Sail is missing or inactive.

## Index

- [Service providers](#service-providers)
  - [Provider lifecycle](#provider-lifecycle)
  - [Creating a provider](#creating-a-provider)
  - [Registering a provider](#registering-a-provider)
- [Database query builder](#database-query-builder)
- [Custom route maps](#custom-route-maps)
  - [Function callback](#function-callback)
  - [Class method callback](#class-method-callback)
  - [Route request hooks](#route-request-hooks)
- [Controller requests](#controller-requests)
- [Flash data after a redirect](#flash-data-after-a-redirect)
- [Controller responses](#controller-responses)
  - [Redirect responses](#redirect-responses)
  - [JSON responses](#json-responses)
  - [View responses](#view-responses)

## Service providers

Service providers are the central place for bootstrapping a plugin that depends
on WP Sail. A provider can bind implementations into WP Sail's shared PHP-DI
container and register WordPress hooks, filters, and routes. The HTTP kernel uses
that same container when resolving controllers and their dependencies.

WP Sail includes providers for translations and its HTTP kernel. Dependent
plugins add their own providers through the `wpsail_service_providers` filter.

### Provider lifecycle

WP Sail uses a single provider phase. On WordPress's `plugins_loaded` action, the
application creates each configured provider and calls its `register()` method.
A provider uses that method to define container services and attach WordPress
hooks or filters.

Runtime work should remain deferred to WordPress callbacks such as `init` or
`template_redirect`. Avoid resolving a service during provider registration when
its dependencies might be supplied by another provider; resolve it inside the
deferred callback instead. WP Sail's HTTP provider follows this rule by defining
the kernel during registration and resolving it later on `init`.

### Creating a provider

Extend `WPSail\Support\ServiceProvider` and implement its `register()` method.
The `container()` method returns the application-wide PHP-DI container.

```php
<?php

namespace Acme\Providers;

use Acme\Contracts\ProductRepository;
use Acme\Repositories\WordPressProductRepository;
use WPSail\Support\ServiceProvider;

use function DI\factory;

final class AcmeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container()->set(
            ProductRepository::class,
            factory(
                fn(): ProductRepository => new WordPressProductRepository(),
            ),
        );

        add_filter('wpsail_route_collection', [$this, 'register_routes']);
    }

    public function register_routes(array $routes): array
    {
        $routes['acme'] = [
            'namespace' => 'Acme\\Http\\Controllers',
        ];

        return $routes;
    }
}
```

Providers also receive the `WPSail\App` instance through their constructor.
Use `$this->app()` to access it, `$this->app()->make($id)` to resolve a service,
or `$this->container()` to define bindings directly.

### Registering a provider

Add provider class names from the dependent plugin's main file. Register the
filter while the plugin file is being loaded so it runs before `plugins_loaded`:

```php
use Acme\Providers\AcmeServiceProvider;

add_filter(
    'wpsail_service_providers',
    static function (array $providers): array {
        $providers[] = AcmeServiceProvider::class;

        return $providers;
    },
);
```

The filter must return an array of provider class names. Each provider must
extend `WPSail\Support\ServiceProvider`.

## Database query builder

`WPSail\Database\Builder` provides a fluent interface over the global WordPress
`wpdb` instance. Pass table names without the WordPress database prefix; `table()`
and the join methods add the current site's prefix automatically.

```php
use WPSail\Database\Builder;

$products = (new Builder())
    ->table('products') // Queries wp_products when the prefix is wp_.
    ->select('id', 'name', 'price')
    ->where('status', '=', 'published')
    ->where_between('price', 10, 100)
    ->order_by_desc('id')
    ->limit(20)
    ->get();
```

`get()` and its `all()` alias return rows as associative arrays. Use `first()`
to retrieve one row; it returns `false` when no row matches.

### Building queries

Builder methods are chainable until an execution method is called:

| Method | Purpose |
| --- | --- |
| `table($table)` | Select a table and add the site's WordPress prefix. |
| `select(...$columns)` | Set the selected columns. The default is `*`. |
| `distinct()` | Add `DISTINCT` to a select query. |
| `where($column, $operator, $value)` | Add a `WHERE` condition. Multiple calls are joined with `AND`. |
| `or_where($column, $operator, $value)` | Add a condition joined with `OR`. |
| `where_in($column, $values)` | Add a `WHERE IN` condition. |
| `where_between($column, $start, $end)` | Add a `WHERE BETWEEN` condition. |
| `left_join($table, $condition)` | Add a left join. The condition is a two-item array containing the columns to compare. |
| `right_join($table, $condition)` | Add a right join. |
| `join($table, $condition, $type)` | Add a join with an explicit type such as `INNER`. |
| `group_by($column)` | Add a `GROUP BY` clause. |
| `order_by($column, $direction)` | Add an `ORDER BY` clause. May be called more than once. |
| `order_by_desc($column)` | Order a column in descending order. |
| `limit($limit)` / `take($limit)` | Limit the number of returned rows. |
| `offset($offset)` | Skip rows. The offset is applied when a positive limit is set. |

For qualified join columns, use the builder's public `prefix` property so the
query also works on sites with a non-default prefix:

```php
$query = (new Builder())->table('orders');
$prefix = $query->prefix;

$orders = $query
    ->select("{$prefix}orders.id", "{$prefix}customers.email")
    ->left_join('customers', [
        "{$prefix}orders.customer_id",
        "{$prefix}customers.id",
    ])
    ->get();
```

### Writing data

Use an associative array for inserts and updates:

```php
$builder = (new Builder())->table('products');

$product_id = $builder->insert([
    'name' => 'Desk lamp',
    'status' => 'draft',
]);

$affected = (new Builder())
    ->table('products')
    ->where('id', '=', $product_id)
    ->update(['status' => 'published']);

$deleted = (new Builder())
    ->table('products')
    ->where('id', '=', $product_id)
    ->delete();
```

`insert()` returns the inserted ID. `update()`, `delete()`, `truncate()`, and
`statement()` return the value produced by the corresponding `wpdb` operation.
`truncate()` removes every row from the selected table, while `statement($sql)`
executes a raw SQL statement. After a select, update, delete, truncate, or raw
statement, the generated SQL is available in the builder's public `query`
property for debugging.

> **SQL safety:** Builder values, identifiers, operators, ordering, and raw
> statements are interpolated into SQL; the class does not bind parameters or
> call `wpdb::prepare()`. Do not pass request data or other untrusted input to
> these methods. Use WordPress's prepared-query APIs when any part of a query is
> dynamic.

## Custom route maps

Add controller namespaces to the HTTP Kernel with the
`wpsail_route_collection` filter. Each array key is an endpoint and its
`namespace` value is the namespace in which WP Sail resolves controllers.
Register the filter before WordPress runs the `init` hook, because that is when
WP Sail boots the kernel and creates its rewrite rules.

The following map resolves a request such as
`/WCMApi/Payment/capture` to the `capture()` method on
`WCMultiStox\Http\Controllers\Api\Payment`:

### Function callback

Use a named function when the route registration does not belong to a class:

```php
add_filter('wpsail_route_collection', 'register_multistox_routes');

function register_multistox_routes(array $routes): array
{
    $routes['WCMApi'] = [
        'namespace' => 'WCMultiStox\\Http\\Controllers\\Api',
    ];

    return $routes;
}
```

### Class method callback

For an object-oriented plugin, register an instance method from the class that
boots the integration:

```php
class MultiStoxPlugin
{
    public function __construct()
    {
        add_filter(
            'wpsail_route_collection',
            [$this, 'register_multistox_routes'],
        );
    }

    public function register_multistox_routes(array $routes): array
    {
        $routes['WCMApi'] = [
            'namespace' => 'WCMultiStox\\Http\\Controllers\\Api',
        ];

        return $routes;
    }
}

new MultiStoxPlugin();
```

The route format is `/{endpoint}/{controller}/{action}`. If the action segment
is omitted, WP Sail calls `index()`. Endpoint keys are case-sensitive when
rewrite rules match the incoming URL, so use the same spelling in the map and
request path. After adding or changing an endpoint, refresh **Settings →
Permalinks** once (or call `flush_rewrite_rules()` during plugin activation) so
WordPress stores the new rewrite rules. Do not flush rewrite rules on every
request.

### Route request hooks

WP Sail dispatches endpoint-scoped request actions after `wpsail_route_init`
and before resolving the controller. For a request to
`/WCMApi/Payment/capture`, the actions run from broadest to most specific:

```text
wpsail_http_request_wcmapi
wpsail_http_request_wcmapi_payment
wpsail_http_request_wcmapi_payment_capture
```

The endpoint, controller, and action portions of these hook names are converted
to lowercase. Each callback receives the current `WPSail\Http\Request`:

```php
use WPSail\Http\Request;

add_action(
    'wpsail_http_request_wcmapi_payment_capture',
    static function (Request $request): void {
        // Run logic for this route before its controller is resolved.
    },
);
```

There is no unscoped `wpsail_http_request` action or separate
`wpsail_authentication_*` action family. Register authentication and other
request middleware on the appropriate scoped `wpsail_http_request_*` action.

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

Return `ViewResponse` from actions that render a PHP template. Pass an absolute,
trusted template path as the first argument and provide template variables with
the named `data` argument:

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

        return new ViewResponse(
            ACME_PLUGIN_PATH . 'resources/views/page.php',
            ViewResponse::HTTP_OK,
            data: ['title' => $title],
        );
    }
}
```

The keys in `data` become variables in the template. Escape every dynamic value
with the appropriate WordPress escaping function:

```php
<!-- resources/views/page.php -->
<h1><?= esc_html($title); ?></h1>
```

Custom response headers remain the third constructor argument, while template
data is the fourth:

```php
return new ViewResponse(
    ACME_PLUGIN_PATH . 'resources/views/page.php',
    ViewResponse::HTTP_OK,
    ['X-Frame-Options' => 'SAMEORIGIN'],
    ['title' => $title],
);
```

Do not construct the template path from request data. `ViewResponse` rejects
paths that do not reference a readable file. It renders the file immediately
and uses `text/html` with the WordPress site charset. Both response classes
inherit the standard HttpFoundation response API, including methods such as
`headers->set()`, `setStatusCode()`, and `setContent()`.
