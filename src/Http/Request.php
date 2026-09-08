<?php

namespace WPSail\Http;

use Aimeos\Macro\Macroable;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

class Request extends SymfonyRequest
{
    use Macroable;

    /**
     * Capture the current WordPress request.
     *
     * WordPress adds slashes to request globals during bootstrap. Normalize the
     * captured values without modifying the globals themselves.
     *
     * @return static
     */
    public static function capture(): static
    {
        $request = static::createFromGlobals();

        if (!function_exists('wp_unslash')) {
            return $request;
        }

        // remove the slashes added by wordpress to the request url
        $request->query->replace(wp_unslash($request->query->all()));
        $request->request->replace(wp_unslash($request->request->all()));
        $request->cookies->replace(wp_unslash($request->cookies->all()));
        $request->server->replace(wp_unslash($request->server->all()));

        $request->headers = new HeaderBag($request->server->getHeaders());

        return $request;
    }

    /**
     * Retrieve the session attached by the HTTP kernel.
     *
     * @return FlashBagAwareSessionInterface
     */
    public function getSession(): FlashBagAwareSessionInterface
    {
        return parent::getSession();
    }
}
