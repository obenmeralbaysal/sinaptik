<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->withHeader('origin', 'http://localhost');
        $this->withHeader('referer', 'http://localhost/');
    }

    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        $this->flushSession();
        $this->defaultCookies = [];
        $this->unencryptedCookies = [];

        return parent::actingAs($user, $guard);
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->withCredentials = true;
        $response = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);

        foreach ($response->headers->getCookies() as $cookie) {
            $expired = $cookie->getExpiresTime() !== 0 && $cookie->getExpiresTime() <= time();
            if ($expired) {
                unset($this->unencryptedCookies[$cookie->getName()]);

                continue;
            }
            $this->unencryptedCookies[$cookie->getName()] = $cookie->getValue();
        }

        return $response;
    }
}
