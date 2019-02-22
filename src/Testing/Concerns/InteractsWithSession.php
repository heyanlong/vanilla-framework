<?php

namespace Vanilla\Testing\Concerns;

use PHPUnit_Framework_Assert as PHPUnit;

trait InteractsWithSession
{
    /**
     * Set the session to the given array.
     *
     * @param  array  $data
     * @return $this
     */
    public function withSession(array $data)
    {
        $this->session($data);

        return $this;
    }

    /**
     * Set the session to the given array.
     *
     * @param  array  $data
     * @return void
     */
    public function session(array $data)
    {
        $this->startSession();

        foreach ($data as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Start the session for the application.
     *
     * @return void
     */
    protected function startSession()
    {
        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Flush all of the current session data.
     *
     * @return void
     */
    public function flushSession()
    {
        $this->startSession();
        $_SESSION = [];
    }

    /**
     * Assert that the session has a given value.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return void
     */
    public function seeInSession($key, $value = null)
    {
        $this->assertSessionHas($key, $value);

        return $this;
    }

    /**
     * Assert that the session has a given value.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return void
     */
    public function assertSessionHas($key, $value = null)
    {
        if (is_array($key)) {
            return $this->assertSessionHasAll($key);
        }

        if (is_null($value)) {
            PHPUnit::assertTrue(isset($_SESSION[$key]), "Session missing key: $key");
        } else {
            PHPUnit::assertEquals($value, $_SESSION[$key]);
        }
    }

    /**
     * Assert that the session has a given list of values.
     *
     * @param  array  $bindings
     * @return void
     */
    public function assertSessionHasAll(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if (is_int($key)) {
                $this->assertSessionHas($value);
            } else {
                $this->assertSessionHas($key, $value);
            }
        }
    }

    /**
     * Assert that the session does not have a given key.
     *
     * @param  string|array  $key
     * @return void
     */
    public function assertSessionMissing($key)
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                $this->assertSessionMissing($k);
            }
        } else {
            PHPUnit::assertFalse(isset($_SESSION[$key]), "Session has unexpected key: $key");
        }
    }
    

    /**
     * Assert that the session has old input.
     *
     * @return void
     */
    public function assertHasOldInput()
    {
        $this->assertSessionHas('_old_input');
    }
}
