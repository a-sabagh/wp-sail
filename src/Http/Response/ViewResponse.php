<?php

namespace WPSail\Http\Response;

use Symfony\Component\HttpFoundation\Response;

class ViewResponse extends Response
{
    public function __construct(?string $content = '', int $status = 200, array $headers = [])
    {
        parent::__construct($content, $status, $headers);

        if (!$this->headers->has('Content-Type')) {
            $charset = function_exists('get_option') ? get_option('blog_charset') : 'UTF-8';

            $this->headers->set(
                'Content-Type',
                'text/html; charset=' . $charset,
            );
        }
    }
}
