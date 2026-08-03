<?php

namespace WPSail\Http;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use UnexpectedValueException;
use WPSail\Http\Exception\RouteNotFoundException;
use WPSail\Http\Response\JsonResponse;
use WPSail\Http\Response\ViewResponse;

class Kernel
{
    /**
     * Initialize the kernel and register its WordPress lifecycle hooks.
     *
     * @param ContainerInterface $container The service container used to resolve controllers.
     *
     * @see \WPSail\Tests\Http\KernelResponseTest
     */
    public function __construct(
        protected ContainerInterface $container,
    ) {
        $this->add_rewrite_rule();

        add_action('template_redirect', [$this,'handle']);
        add_action('shutdown', [$this, 'handle_shutdown']);
    }

    /**
     * Retrieve the filtered route collection as an array.
     * Any additional plugins that use this plugins as a boilerplate
     * must apply their route data through this filter.
     *
     * @return array<string, array<string, mixed>>
     *
     * @see \WPSail\Tests\Http\KernelResponseTest
     */
    protected function get_map(): array
    {
        return apply_filters('wpsail_route_collection', []);
    }

    /**
     * Register route rewrite rules and query variables with WordPress.
     * Link structure must be define as /{endpoint}/{controller}/{action}/{params}.
     * Query variables: `wpsail_class`, `wpsail_action`, `wpsail_params`, and `wpsail_endpoint`.
     *
     * @return void
     *
     * @see \WPSail\Tests\Http\KernelResponseTest
     */
    public function add_rewrite_rule()
    {
        foreach ($this->get_map() as $slug => $data) {
            add_rewrite_rule(
                "^{$slug}/([^/]*)/?([^/]*)/?([^/]*)/?$",
                'index.php?wpsail_class=$matches[1]&wpsail_action=$matches[2]&wpsail_params=$matches[3]&wpsail_endpoint=' . $slug,
                "top",
            );

            add_rewrite_tag("%wpsail_class%", "([^/]*)");
            add_rewrite_tag("%wpsail_action%", "([^/]*)");
            add_rewrite_tag("%wpsail_params%", "([^/]*)");
            add_rewrite_tag("%wpsail_endpoint%", "([^/]*)");
        }
    }

    /**
     * Resolve and dispatch the current routed WordPress request.
     *
     * After resolving the endpoint, controller, action, and request parameters,
     * the following hooks run in lifecycle with the following pattern
     * wpsail_{hook}_{endpoint}_{controller}_{action}
     *
     * @return void
     *
     * @see \WPSail\Tests\Http\KernelResponseTest
     * @see self::handle_shutdown()
     */
    public function handle()
    {
        global $endpoint;

        $endpoint = get_query_var("wpsail_endpoint");

        if (empty($endpoint)) {
            unset($endpoint);

            return;
        }

        global $module,$action,$route_expression,$request_params;

        $map = $this->get_map();

        $module = get_query_var("wpsail_class");
        $action = get_query_var("wpsail_action") ?: 'index';
        $params = get_query_var("wpsail_params");

        $endpoint_tolower = strtolower($endpoint);
        $module_tolower = strtolower($module);
        $action_tolower = strtolower($action);

        parse_str($params, $request_params);

        $route_expression = "{$endpoint_tolower}/{$module_tolower}/{$action_tolower}";

        try {
            if (!isset($map[$endpoint])) {
                throw new RouteNotFoundException($endpoint, $module, $action);
            }

            $namespace = $map[$endpoint]['namespace'] ?? null;

            if (!is_string($namespace) || '' === $namespace) {
                throw new UnexpectedValueException(
                    sprintf(
                        __('Route endpoint "%s" must define a controller namespace.', 'wp-sail'),
                        $endpoint,
                    ),
                );
            }

            do_action("wpsail_route_init", $endpoint, $module, $action);
            do_action("wpsail_authentication_{$endpoint_tolower}");
            do_action("wpsail_authentication_{$endpoint_tolower}_{$module_tolower}");
            do_action("wpsail_authentication_{$endpoint_tolower}_{$module_tolower}_{$action_tolower}");

            do_action("wpsail_http_request");
            do_action("wpsail_http_request_{$endpoint_tolower}");
            do_action("wpsail_http_request_{$endpoint_tolower}_{$module_tolower}");
            do_action("wpsail_http_request_{$endpoint_tolower}_{$module_tolower}_{$action_tolower}");

            $object = $this->resolve_controller($namespace, $endpoint, $module, $action);

            do_action('wpsail_request_start', $action, $request_params);

            $response = $this->dispatch_response($object, $action, $request_params);

            $this->send_response($response);

        } catch (Throwable $exception) {
            do_action('wpsail_exception_handling', $exception);

            $this->send_response($this->make_error_response($exception));
        }
    }

    /**
     * Resolve a callable controller from the service container.
     *
     * @param string $namespace  The controller namespace.
     * @param string $endpoint   The matched route endpoint.
     * @param string $controller The controller class name.
     * @param string $action     The controller action name.
     *
     * @return object
     *
     * @throws RouteNotFoundException If the controller or action cannot be resolved.
     *
     * @see \WPSail\Tests\Http\KernelResponseTest
     */
    protected function resolve_controller(string $namespace, string $endpoint, string $controller, string $action): object
    {
        $class = "{$namespace}\\{$controller}";

        try {
            $object = $this->container->get($class);
        } catch (NotFoundExceptionInterface $exception) {
            throw new RouteNotFoundException($endpoint, $controller, $action, $exception);
        }

        if (!is_object($object) || !is_callable([$object, $action])) {
            throw new RouteNotFoundException($endpoint, $controller, $action);
        }

        return $object;
    }

    /**
     * Invoke a controller action and validate its response.
     *
     * @param object               $object     The resolved controller instance.
     * @param string               $action     The controller action name.
     * @param array<string, mixed> $parameters The routed request parameters.
     *
     * @return Response
     *
     * @throws UnexpectedValueException If the action does not return an HttpFoundation response.
     *
     * @see \WPSail\Tests\Http\KernelResponseTest
     */
    protected function dispatch_response(object $object, string $action, array $parameters): Response
    {
        $response = $object->$action($parameters);

        if (!$response instanceof Response) {
            throw new UnexpectedValueException(
                sprintf(
                    __('Route action %1$s::%2$s() must return %3$s; %4$s returned.', 'wp-sail'),
                    $object::class,
                    $action,
                    Response::class,
                    get_debug_type($response),
                ),
            );
        }

        return $response;
    }

    /**
     * Convert a throwable into a response suitable for the current request.
     *
     * @param Throwable $exception The exception or error raised while handling the route.
     *
     * @return Response
     *
     * @see \Symfony\Component\HttpFoundation\JsonResponse
     * @see \WPSail\Tests\Http\KernelResponseTest
     */
    protected function make_error_response(Throwable $exception): Response
    {
        $status = $exception->getCode();

        if ($status < 400 || $status > 599) {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        if (wp_is_json_request()) {
            return new JsonResponse([
                'status' => false,
                'data' => [
                    'code' => $status,
                    'errors' => [
                        [
                            'type' => 'error',
                            'message' => $exception->getMessage(),
                        ],
                    ],
                ],
            ], $status);
        }

        return new ViewResponse(
            apply_filters(
                'wpsail_route_view_response_body',
                esc_html($exception->getMessage()),
            ),
            $status,
        );
    }

    /**
     * Send an HttpFoundation response through the WordPress response lifecycle.
     *
     * @param Response $response The response to send.
     *
     * @return void
     *
     * @see \WPSail\Tests\Http\KernelResponseTest
     */
    protected function send_response(Response $response): void
    {
        if ($response instanceof JsonResponse) {
            send_origin_headers();
        }

        nocache_headers();

        $response->sendHeaders();

        status_header(
            $response->getStatusCode(),
            Response::$statusTexts[$response->getStatusCode()] ?? '',
        );

        $response->sendContent();
        exit;
    }

    /**
     * Dispatch the plugin shutdown action after a routed request.
     *
     * @return void
     *
     * @see \WPSail\Tests\Http\KernelResponseTest
     */
    public function handle_shutdown(): void
    {
        global $endpoint;

        if (empty($endpoint)) {
            return;
        }

        do_action('wpsail_request_shutdown');
    }
}
