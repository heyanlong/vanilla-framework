<?php

namespace Vanilla\Testing\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use PHPUnit_Framework_Assert as PHPUnit;
use PHPUnit_Framework_ExpectationFailedException;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

trait MakesHttpRequests
{
    use InteractsWithPages;

    /**
     * The last response returned by the application.
     *
     * @var \Illuminate\Http\Response
     */
    protected $response;

    /**
     * The current URL being viewed.
     *
     * @var string
     */
    protected $currentUri;

    /**
     * Additional server variables for the request.
     *
     * @var array
     */
    protected $serverVariables = [];


    /**
     * Visit the given URI with a JSON request.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return $this
     */
    public function json($method, $uri, array $data = [], array $headers = [])
    {
        $files = $this->extractFilesFromDataArray($data);

        $content = json_encode($data);

        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);

        $this->call(
            $method, $uri, [], [], $files, $this->transformHeadersToServerVars($headers), $content
        );

        return $this;
    }

    /**
     * Extract the file uploads from the given data array.
     *
     * @param  array  $data
     * @return array
     */
    protected function extractFilesFromDataArray(&$data)
    {
        $files = [];

        foreach ($data as $key => $value) {
            if ($value instanceof SymfonyUploadedFile) {
                $files[$key] = $value;

                unset($data[$key]);
            }
        }

        return $files;
    }

    /**
     * Visit the given URI with a GET request.
     *
     * @param  string  $uri
     * @param  array  $headers
     * @return $this
     */
    public function get($uri, array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('GET', $uri, [], [], [], $server);

        return $this;
    }

    /**
     * Visit the given URI with a POST request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return $this
     */
    public function post($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('POST', $uri, $data, [], [], $server);

        return $this;
    }

    /**
     * Visit the given URI with a PUT request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return $this
     */
    public function put($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('PUT', $uri, $data, [], [], $server);

        return $this;
    }

    /**
     * Visit the given URI with a PATCH request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return $this
     */
    public function patch($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('PATCH', $uri, $data, [], [], $server);

        return $this;
    }

    /**
     * Visit the given URI with a DELETE request.
     *
     * @param  string  $uri
     * @param  array  $data
     * @param  array  $headers
     * @return $this
     */
    public function delete($uri, array $data = [], array $headers = [])
    {
        $server = $this->transformHeadersToServerVars($headers);

        $this->call('DELETE', $uri, $data, [], [], $server);

        return $this;
    }


    /**
     * Assert that the response contains an exact JSON array.
     *
     * @param  array  $data
     * @return $this
     */
    public function seeJsonEquals(array $data)
    {
        $actual = json_encode(Arr::sortRecursive(
            json_decode($this->response->getContent(), true)
        ));

        $this->assertEquals(json_encode(Arr::sortRecursive($data)), $actual);

        return $this;
    }

    /**
     * Assert that the response contains JSON.
     *
     * @param  array|null  $data
     * @param  bool  $negate
     * @return $this
     */
    public function seeJson(array $data = null, $negate = false)
    {
        if (is_null($data)) {
            $this->assertJson(
                $this->response->getContent(), "JSON was not returned from [{$this->currentUri}]."
            );

            return $this;
        }

        try {
            $this->seeJsonEquals($data);

            return $this;
        } catch (PHPUnit_Framework_ExpectationFailedException $e) {
            return $this->seeJsonContains($data, $negate);
        }
    }

    /**
     * Assert that the response doesn't contain JSON.
     *
     * @param  array|null  $data
     * @return $this
     */
    public function dontSeeJson(array $data = null)
    {
        return $this->seeJson($data, true);
    }

    /**
     * Assert that the JSON response has a given structure.
     *
     * @param  array|null  $structure
     * @param  array|null  $responseData
     * @return $this
     */
    public function seeJsonStructure(array $structure = null, $responseData = null)
    {
        if (is_null($structure)) {
            return $this->seeJson();
        }

        if (! $responseData) {
            $responseData = json_decode($this->response->getContent(), true);
        }

        foreach ($structure as $key => $value) {
            if (is_array($value) && $key === '*') {
                $this->assertInternalType('array', $responseData);

                foreach ($responseData as $responseDataItem) {
                    $this->seeJsonStructure($structure['*'], $responseDataItem);
                }
            } elseif (is_array($value)) {
                $this->assertArrayHasKey($key, $responseData);
                $this->seeJsonStructure($structure[$key], $responseData[$key]);
            } else {
                $this->assertArrayHasKey($value, $responseData);
            }
        }

        return $this;
    }

    /**
     * Assert that the response contains the given JSON.
     *
     * @param  array  $data
     * @param  bool  $negate
     * @return $this
     */
    protected function seeJsonContains(array $data, $negate = false)
    {
        $method = $negate ? 'assertFalse' : 'assertTrue';

        $actual = json_encode(Arr::sortRecursive(
            (array) $this->decodeResponseJson()
        ));

        foreach (Arr::sortRecursive($data) as $key => $value) {
            $expected = $this->formatToExpectedJson($key, $value);

            $this->{$method}(
                Str::contains($actual, $expected),
                ($negate ? 'Found unexpected' : 'Unable to find')." JSON fragment [{$expected}] within [{$actual}]."
            );
        }

        return $this;
    }

    /**
     * Validate and return the decoded response JSON.
     *
     * @return array
     */
    protected function decodeResponseJson()
    {
        $decodedResponse = json_decode($this->response->getContent(), true);

        if (is_null($decodedResponse) || $decodedResponse === false) {
            $this->fail('Invalid JSON was returned from the route. Perhaps an exception was thrown?');
        }

        return $decodedResponse;
    }

    /**
     * Format the given key and value into a JSON string for expectation checks.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return string
     */
    protected function formatToExpectedJson($key, $value)
    {
        $expected = json_encode([$key => $value]);

        if (Str::startsWith($expected, '{')) {
            $expected = substr($expected, 1);
        }

        if (Str::endsWith($expected, '}')) {
            $expected = substr($expected, 0, -1);
        }

        return $expected;
    }

    /**
     * Asserts that the status code of the response matches the given code.
     *
     * @param  int  $status
     * @return $this
     */
    protected function seeStatusCode($status)
    {
        $this->assertEquals($status, $this->response->getStatusCode());

        return $this;
    }


    /**
     * Call the given URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array   $parameters
     * @param  array   $cookies
     * @param  array   $files
     * @param  array   $server
     * @param  string  $content
     * @return \Illuminate\Http\Response
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $kernel = $this->app->make('Vanilla\Contracts\Kernel');

        $this->currentUri = $this->prepareUrlForRequest($uri);

        $this->resetPageContext();

        $request = Request::create(
            $this->currentUri, $method, $parameters,
            $cookies, $files, array_replace($this->serverVariables, $server), $content
        );
        $vanRequests = new \Vanilla\Request();


        $vanRequests->createRequestFrom($request->query->all(),$request->request->all(),$request->cookies->all(),$request->server->all(),$request->getContent());
        $this->app['request'] = $vanRequests;
        $response = $kernel->handle();

        return $this->response = $response;
    }

    /**
     * Turn the given URI into a fully qualified URL.
     *
     * @param  string  $uri
     * @return string
     */
    protected function prepareUrlForRequest($uri)
    {
        if (Str::startsWith($uri, '/')) {
            $uri = substr($uri, 1);
        }

        if (! Str::startsWith($uri, 'http')) {
            $uri = $this->baseUrl.'/'.$uri;
        }

        return trim($uri, '/');
    }

    /**
     * Transform headers array to array of $_SERVER vars with HTTP_* format.
     *
     * @param  array  $headers
     * @return array
     */
    protected function transformHeadersToServerVars(array $headers)
    {
        $server = [];
        $prefix = 'HTTP_';

        foreach ($headers as $name => $value) {
            $name = strtr(strtoupper($name), '-', '_');

            if (! Str::startsWith($name, $prefix) && $name != 'CONTENT_TYPE') {
                $name = $prefix.$name;
            }

            $server[$name] = $value;
        }

        return $server;
    }

    /**
     * Assert that the client response has an OK status code.
     *
     * @return $this
     */
    public function assertResponseOk()
    {
        $actual = $this->response->getStatusCode();

        PHPUnit::assertTrue($actual == 200, "Expected status code 200, got {$actual}.");

        return $this;
    }

    /**
     * Assert that the client response has a given code.
     *
     * @param  int  $code
     * @return $this
     */
    public function assertResponseStatus($code)
    {
        $actual = $this->response->getStatusCode();

        PHPUnit::assertEquals($code, $this->response->getStatusCode(), "Expected status code {$code}, got {$actual}.");

        return $this;
    }


    /**
     * Dump the content from the last response.
     *
     * @return void
     */
    public function dump()
    {
        $content = $this->response->getContent();

        $json = json_decode($content);

        if (json_last_error() === JSON_ERROR_NONE) {
            $content = $json;
        }

        dd($content);
    }
}
