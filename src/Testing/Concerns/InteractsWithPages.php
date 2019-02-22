<?php

namespace Vanilla\Testing\Concerns;

use Closure;
use InvalidArgumentException;
use Illuminate\Http\UploadedFile;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Crawler;
use Vanilla\Testing\HttpException;
use Vanilla\Testing\Constraints\HasText;
use Vanilla\Testing\Constraints\HasLink;
use Vanilla\Testing\Constraints\HasValue;
use Vanilla\Testing\Constraints\HasSource;
use Vanilla\Testing\Constraints\IsChecked;
use Vanilla\Testing\Constraints\HasElement;
use Vanilla\Testing\Constraints\IsSelected;
use Vanilla\Testing\Constraints\HasInElement;
use Vanilla\Testing\Constraints\PageConstraint;
use Vanilla\Testing\Constraints\ReversePageConstraint;
use PHPUnit_Framework_ExpectationFailedException as PHPUnitException;

trait InteractsWithPages
{
    /**
     * The DomCrawler instance.
     *
     * @var \Symfony\Component\DomCrawler\Crawler
     */
    protected $crawler;

    /**
     * Nested crawler instances used by the "within" method.
     *
     * @var array
     */
    protected $subCrawlers = [];

    /**
     * All of the stored inputs for the current page.
     *
     * @var array
     */
    protected $inputs = [];

    /**
     * All of the stored uploads for the current page.
     *
     * @var array
     */
    protected $uploads = [];

    /**
     * Visit the given URI with a GET request.
     *
     * @param  string  $uri
     * @return $this
     */
    public function visit($uri)
    {
        return $this->makeRequest('GET', $uri);
    }

    /**
     * Make a request to the application and create a Crawler instance.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $parameters
     * @param  array  $cookies
     * @param  array  $files
     * @return $this
     */
    protected function makeRequest($method, $uri, $parameters = [], $cookies = [], $files = [])
    {
        $uri = $this->prepareUrlForRequest($uri);

        $this->call($method, $uri, $parameters, $cookies, $files);

        $this->clearInputs()->followRedirects()->assertPageLoaded($uri);

        $this->currentUri = $uri;

        $this->crawler = new Crawler($this->response->getContent(), $this->currentUri);

        return $this;
    }

    /**
     * Clean the crawler and the subcrawlers values to reset the page context.
     *
     * @return void
     */
    protected function resetPageContext()
    {
        $this->crawler = null;

        $this->subCrawlers = [];
    }


    /**
     * Follow redirects from the last response.
     *
     * @return $this
     */
    protected function followRedirects()
    {
        while ($redirect = $this->response->getHeaders()->get('Location')){
            $this->makeRequest('GET', $redirect);
        }

        return $this;
    }

    /**
     * Clear the inputs for the current page.
     *
     * @return $this
     */
    protected function clearInputs()
    {
        $this->inputs = [];

        $this->uploads = [];

        return $this;
    }
    

    /**
     * Assert that a given page successfully loaded.
     *
     * @param  string  $uri
     * @param  string|null  $message
     * @return void
     *
     * @throws \Vanilla\Testing\HttpException
     */
    protected function assertPageLoaded($uri, $message = null)
    {
        $status = $this->response->getStatusCode();

        try {
            $this->assertEquals(200, $status);
        } catch (PHPUnitException $e) {
            $message = $message ?: "A request to [{$uri}] failed. Received status code [{$status}].";

            $responseException = isset($this->response->exception)
                    ? $this->response->exception : null;

            throw new HttpException($message, null, $responseException);
        }
    }

    /**
     * Narrow the test content to a specific area of the page.
     *
     * @param  string  $element
     * @param  \Closure  $callback
     * @return $this
     */
    public function within($element, Closure $callback)
    {
        $this->subCrawlers[] = $this->crawler()->filter($element);

        $callback();

        array_pop($this->subCrawlers);

        return $this;
    }

    /**
     * Get the current crawler according to the test context.
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function crawler()
    {
        if (! empty($this->subCrawlers)) {
            return end($this->subCrawlers);
        }

        return $this->crawler;
    }

    /**
     * Assert the given constraint.
     *
     * @param  \Illuminate\Foundation\Testing\Constraints\PageConstraint  $constraint
     * @param  bool  $reverse
     * @param  string  $message
     * @return $this
     */
    protected function assertInPage(PageConstraint $constraint, $reverse = false, $message = '')
    {
        if ($reverse) {
            $constraint = new ReversePageConstraint($constraint);
        }

        self::assertThat(
            $this->crawler() ?: $this->response->getContent(),
            $constraint, $message
        );

        return $this;
    }

    /**
     * Assert that a given string is seen on the current HTML.
     *
     * @param  string  $text
     * @param  bool  $negate
     * @return $this
     */
    public function see($text, $negate = false)
    {
        return $this->assertInPage(new HasSource($text), $negate);
    }

    /**
     * Assert that a given string is not seen on the current HTML.
     *
     * @param  string  $text
     * @return $this
     */
    public function dontSee($text)
    {
        return $this->assertInPage(new HasSource($text), true);
    }

    /**
     * Assert that an element is present on the page.
     *
     * @param  string  $selector
     * @param  array  $attributes
     * @param  bool  $negate
     * @return $this
     */
    public function seeElement($selector, array $attributes = [], $negate = false)
    {
        return $this->assertInPage(new HasElement($selector, $attributes), $negate);
    }

    /**
     * Assert that an element is not present on the page.
     *
     * @param  string  $selector
     * @param  array  $attributes
     * @return $this
     */
    public function dontSeeElement($selector, array $attributes = [])
    {
        return $this->assertInPage(new HasElement($selector, $attributes), true);
    }

    /**
     * Assert that a given string is seen on the current text.
     *
     * @param  string  $text
     * @param  bool  $negate
     * @return $this
     */
    public function seeText($text, $negate = false)
    {
        return $this->assertInPage(new HasText($text), $negate);
    }

    /**
     * Assert that a given string is not seen on the current text.
     *
     * @param  string  $text
     * @return $this
     */
    public function dontSeeText($text)
    {
        return $this->assertInPage(new HasText($text), true);
    }

    /**
     * Assert that a given string is seen inside an element.
     *
     * @param  string  $element
     * @param  string  $text
     * @param  bool  $negate
     * @return $this
     */
    public function seeInElement($element, $text, $negate = false)
    {
        return $this->assertInPage(new HasInElement($element, $text), $negate);
    }

    /**
     * Assert that a given string is not seen inside an element.
     *
     * @param  string  $element
     * @param  string  $text
     * @return $this
     */
    public function dontSeeInElement($element, $text)
    {
        return $this->assertInPage(new HasInElement($element, $text), true);
    }

    /**
     * Assert that a given link is seen on the page.
     *
     * @param  string $text
     * @param  string|null $url
     * @param  bool  $negate
     * @return $this
     */
    public function seeLink($text, $url = null, $negate = false)
    {
        return $this->assertInPage(new HasLink($text, $url), $negate);
    }

    /**
     * Assert that a given link is not seen on the page.
     *
     * @param  string  $text
     * @param  string|null  $url
     * @return $this
     */
    public function dontSeeLink($text, $url = null)
    {
        return $this->assertInPage(new HasLink($text, $url), true);
    }

    /**
     * Assert that an input field contains the given value.
     *
     * @param  string  $selector
     * @param  string  $expected
     * @param  bool  $negate
     * @return $this
     */
    public function seeInField($selector, $expected, $negate = false)
    {
        return $this->assertInPage(new HasValue($selector, $expected), $negate);
    }

    /**
     * Assert that an input field does not contain the given value.
     *
     * @param  string  $selector
     * @param  string  $value
     * @return $this
     */
    public function dontSeeInField($selector, $value)
    {
        return $this->assertInPage(new HasValue($selector, $value), true);
    }

    /**
     * Assert that the expected value is selected.
     *
     * @param  string  $selector
     * @param  string  $value
     * @param  bool  $negate
     * @return $this
     */
    public function seeIsSelected($selector, $value, $negate = false)
    {
        return $this->assertInPage(new IsSelected($selector, $value), $negate);
    }

    /**
     * Assert that the given value is not selected.
     *
     * @param  string  $selector
     * @param  string  $value
     * @return $this
     */
    public function dontSeeIsSelected($selector, $value)
    {
        return $this->assertInPage(new IsSelected($selector, $value), true);
    }

    /**
     * Assert that the given checkbox is selected.
     *
     * @param  string  $selector
     * @param  bool  $negate
     * @return $this
     */
    public function seeIsChecked($selector, $negate = false)
    {
        return $this->assertInPage(new IsChecked($selector), $negate);
    }

    /**
     * Assert that the given checkbox is not selected.
     *
     * @param  string  $selector
     * @return $this
     */
    public function dontSeeIsChecked($selector)
    {
        return $this->assertInPage(new IsChecked($selector), true);
    }
}
