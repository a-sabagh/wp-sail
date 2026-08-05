<?php

namespace WPSail\Tests\Http;

use PHPUnit\Framework\TestCase;
use WPSail\Http\Request;

final class RequestTest extends TestCase
{
    public function test_capture_normalizes_wordpress_slashed_input(): void
    {
        $original_get = $_GET;
        $original_post = $_POST;
        $original_cookie = $_COOKIE;
        $original_server = $_SERVER;

        $_GET = ['search' => addslashes("WordPress's request")];
        $_POST = ['title' => addslashes('A "quoted" title')];
        $_COOKIE = ['preference' => addslashes("reader's")];
        $_SERVER['REQUEST_METHOD'] = 'POST';

        try {
            $request = Request::capture();

            $this->assertSame("WordPress's request", $request->query->get('search'));
            $this->assertSame('A "quoted" title', $request->request->get('title'));
            $this->assertSame("reader's", $request->cookies->get('preference'));
        } finally {
            $_GET = $original_get;
            $_POST = $original_post;
            $_COOKIE = $original_cookie;
            $_SERVER = $original_server;
        }
    }
}
