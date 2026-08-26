<?php

namespace WPSail\Http;

use DI\Container;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\AutoExpireFlashBag;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
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
        protected Container $container,
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
     * Link structure must be define as /{endpoint}/{controller}/{action}.
     * Query variables: `wpsail_class`, `wpsail_action`, and `wpsail_endpoint`.
     *
     * @return void
     *
     * @see \WPSail\Tests\Http\KernelResponseTest
     */
    public function add_rewrite_rule()
    {
        foreach ($this->get_map() as $slug => $data) {
            add_rewrite_rule(
                "^{$slug}/([^/]*)/?([^/]*)/?$",
                'index.php?wpsail_class=$matches[1]&wpsail_action=$matches[2]&wpsail_endpoint=' . $slug,
                "top",
            );

            add_rewrite_tag("%wpsail_class%", "([^/]*)");
            add_rewrite_tag("%wpsail_action%", "([^/]*)");
            add_rewrite_tag("%wpsail_endpoint%", "([^/]*)");
        }
    }

    /**
     * Resolve and dispatch the current routed WordPress request.
     *
     * After resolving the endpoint, controller, and action, the following hooks
     * run in lifecycle with the following pattern
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

        global $module,$action,$route_expression;

        $map = $this->get_map();

        $module = get_query_var("wpsail_class");
        $action = get_query_var("wpsail_action") ?: 'index';

        $endpoint_tolower = strtolower($endpoint);
        $module_tolower = strtolower($module);
        $action_tolower = strtolower($action);

        $route_expression = "{$endpoint_tolower}/{$module_tolower}/{$action_tolower}";

        $request = Request::capture();

        $request->attributes->set('_endpoint', $endpoint);
        $request->attributes->set('_controller', $module);
        $request->attributes->set('_action', $action);
        $request->attributes->set('_route', $route_expression);

        $this->attach_session($request);

        $this->container->set(Request::class, $request);

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

            do_action("wpsail_route_init", $endpoint, $module, $action, $request);

            do_action("wpsail_http_request_{$endpoint_tolower}", $request);
            do_action("wpsail_http_request_{$endpoint_tolower}_{$module_tolower}", $request);
            do_action("wpsail_http_request_{$endpoint_tolower}_{$module_tolower}_{$action_tolower}", $request);

            $object = $this->resolve_controller($namespace, $endpoint, $module, $action);

            do_action('wpsail_request_start', $action, $request);

            $response = $this->dispatch_response($object, $action, $request);

        } catch (Throwable $exception) {
            do_action('wpsail_exception_handling', $exception);

            $response = $this->make_error_response($exception);
        }

        $this->save_session($request);

        $this->send_response($response);
    }

    /**
     * Make flash data available without starting a session until it is used.
     *
     * @param Request $request The current HTTP request.
     *
     * @return void
     */
    protected function attach_session(Request $request): void
    {
        $request->setSessionFactory(fn(): FlashBagAwareSessionInterface => $this->make_session());
    }

    /**
     * Create a session that keeps WP Sail flash data isolated.
     *
     * @return FlashBagAwareSessionInterface
     */
    protected function make_session(): FlashBagAwareSessionInterface
    {
        return new Session(
            flashes: new AutoExpireFlashBag('_wpsail_flashes'),
        );
    }

    /**
     * Persist an initialized session and release its lock before responding.
     *
     * @param Request $request The current HTTP request.
     *
     * @return void
     */
    protected function save_session(Request $request): void
    {
        // Avoid resolving and saving a lazy session that was never used.
        if (!$request->hasSession(true)) {
            return;
        }

        $session = $request->getSession();

        // check whether PHP session storage is opened
        if ($session->isStarted()) {
            $session->save();
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
     * @see \WPSail\Tests\Http\KernelDependencyInjectionTest
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
     * @param object  $object  The resolved controller instance.
     * @param string  $action  The controller action name.
     * @param Request $request The current HTTP request.
     *
     * @return Response
     *
     * @throws UnexpectedValueException If the action does not return an HttpFoundation response.
     *
     * @see \WPSail\Tests\Http\KernelResponseTest
     */
    protected function dispatch_response(object $object, string $action, Request $request): Response
    {
        $this->container->set(Request::class, $request);

        $response = $this->container->call([$object, $action]);

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
            dirname(__DIR__, 2) . '/resources/views/error.php',
            $status,
            data: [
                'body' => apply_filters(
                    'wpsail_route_view_response_body',
                    esc_html($exception->getMessage()),
                ),
            ],
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
