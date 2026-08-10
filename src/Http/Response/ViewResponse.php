<?php

namespace WPSail\Http\Response;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ViewResponse extends Response
{
    /**
     * Create an HTML response by rendering a PHP template file.
     *
     * @param string               $template The absolute path to a readable PHP template.
     * @param int                  $status   The HTTP response status.
     * @param array<string, mixed> $headers  The HTTP response headers.
     * @param array<string, mixed> $data     Variables made available to the template.
     */
    public function __construct(string $template, int $status = 200, array $headers = [], array $data = []) 
    {
        parent::__construct($this->render_template($template, $data), $status, $headers);

        if (!$this->headers->has('Content-Type')) {
            $charset = function_exists('get_option') ? get_option('blog_charset') : 'UTF-8';

            $this->headers->set(
                'Content-Type',
                'text/html; charset=' . $charset,
            );
        }
    }

    /**
     * Render a PHP template into an isolated output buffer.
     *
     * @param array<string, mixed> $data
     */
    protected function render_template(string $template, array $data): string
    {
        if (!is_file($template) || !is_readable($template)) {
            throw new InvalidArgumentException(
                sprintf('View template [%s] must be a readable file.', $template),
            );
        }

        ob_start();

        try {
            // the `EXTR_SKIP` parameter prevent from overwriting variables
            // that already exists in current scope
            extract($data, EXTR_SKIP);

            require $template;

            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }
    }
}
