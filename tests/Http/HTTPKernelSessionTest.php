<?php

namespace WPSail\Tests\Http;

use DI\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Flash\AutoExpireFlashBag;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use WPSail\Http\Kernel;
use WPSail\Http\Request;

final class HTTPKernelSessionTest extends TestCase
{
    private SessionKernel $kernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernel = new SessionKernel(new Container());
    }

    protected function tearDown(): void
    {
        remove_action('template_redirect', [$this->kernel, 'handle']);
        remove_action('shutdown', [$this->kernel, 'handle_shutdown']);

        parent::tearDown();
    }

    public function test_request_session_remains_lazy_when_it_is_not_used(): void
    {
        $storage = new TestSessionStorage();
        $this->kernel->useSession($this->makeSession($storage));

        $request = new Request();
        $this->kernel->attachSession($request);

        $this->assertTrue($request->hasSession());
        $this->assertFalse($request->hasSession(true));

        $this->kernel->saveSession($request);

        $this->assertFalse($storage->isStarted());
    }

    public function test_flash_data_is_available_for_one_following_request(): void
    {
        $errors = ['email' => ['The email field is required.']];
        $old = ['email' => 'person@example.com'];

        $postStorage = new TestSessionStorage();
        $this->kernel->useSession($this->makeSession($postStorage));

        $postRequest = new Request();
        $this->kernel->attachSession($postRequest);

        $flash = $postRequest->getSession()->getFlashBag();
        $flash->set('wpsail.validation_errors', $errors);
        $flash->set('wpsail.old_input', $old);

        $this->kernel->saveSession($postRequest);

        $this->assertArrayHasKey('_wpsail_flashes', $postStorage->sessionData());

        $redirectStorage = new TestSessionStorage();
        $redirectStorage->setSessionData($postStorage->sessionData());
        $this->kernel->useSession($this->makeSession($redirectStorage));

        $redirectRequest = new Request();
        $this->kernel->attachSession($redirectRequest);

        $flash = $redirectRequest->getSession()->getFlashBag();

        $this->assertSame($errors, $flash->get('wpsail.validation_errors'));
        $this->assertSame($old, $flash->get('wpsail.old_input'));

        $this->kernel->saveSession($redirectRequest);

        $followingStorage = new TestSessionStorage();
        $followingStorage->setSessionData($redirectStorage->sessionData());
        $this->kernel->useSession($this->makeSession($followingStorage));

        $followingRequest = new Request();
        $this->kernel->attachSession($followingRequest);

        $this->assertSame([], $followingRequest->getSession()->getFlashBag()->get('wpsail.validation_errors'));
        $this->assertSame([], $followingRequest->getSession()->getFlashBag()->get('wpsail.old_input'));
    }

    private function makeSession(TestSessionStorage $storage): Session
    {
        return new Session(
            $storage,
            flashes: new AutoExpireFlashBag('_wpsail_flashes'),
        );
    }
}

final class SessionKernel extends Kernel
{
    private ?FlashBagAwareSessionInterface $session = null;

    public function useSession(FlashBagAwareSessionInterface $session): void
    {
        $this->session = $session;
    }

    public function attachSession(Request $request): void
    {
        $this->attach_session($request);
    }

    public function saveSession(Request $request): void
    {
        $this->save_session($request);
    }

    protected function make_session(): FlashBagAwareSessionInterface
    {
        return $this->session ?? parent::make_session();
    }
}

final class TestSessionStorage extends MockArraySessionStorage
{
    /**
     * @return array<string, mixed>
     */
    public function sessionData(): array
    {
        return $this->data;
    }
}
