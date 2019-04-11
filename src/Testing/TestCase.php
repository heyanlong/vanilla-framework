<?php

namespace Vanilla\Testing;

//use Mockery;


use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Vanilla\Application;
use Vanilla\Exceptions\MethodNotAllowedHttpException;
use Vanilla\Exceptions\NotFoundHttpException;
use Vanilla\Http\Request;
use Vanilla\Http\Response;

/**
 * @method void assertArrayHasKey($key, $array, string $message = '')
 * @method void assertArraySubset($subset, $array, bool $checkForObjectIdentity = false, string $message = '')
 * @method void assertArrayNotHasKey($key, $array, string $message = '')
 * @method void assertContains($needle, $haystack, string $message = '', bool $ignoreCase = false, bool $checkForObjectIdentity = true, bool $checkForNonObjectIdentity = false)
 * @method void assertAttributeContains($needle, string $haystackAttributeName, $haystackClassOrObject, string $message = '', bool $ignoreCase = false, bool $checkForObjectIdentity = true, bool $checkForNonObjectIdentity = false)
 * @method void assertNotContains($needle, $haystack, string $message = '', bool $ignoreCase = false, bool $checkForObjectIdentity = true, bool $checkForNonObjectIdentity = false)
 * @method void assertAttributeNotContains($needle, string $haystackAttributeName, $haystackClassOrObject, string $message = '', bool $ignoreCase = false, bool $checkForObjectIdentity = true, bool $checkForNonObjectIdentity = false)
 * @method void assertContainsOnly(string $type, iterable $haystack, bool $isNativeType = false, string $message = '')
 * @method void assertContainsOnlyInstancesOf(string $className, iterable $haystack, string $message = '')
 * @method void assertAttributeContainsOnly(string $type, string $haystackAttributeName, $haystackClassOrObject, bool $isNativeType = false, string $message = '')
 * @method void assertNotContainsOnly(string $type, iterable $haystack, bool $isNativeType = false, string $message = '')
 * @method void assertAttributeNotContainsOnly(string $type, string $haystackAttributeName, $haystackClassOrObject, bool $isNativeType = false, string $message = '')
 * @method void assertCount(int $expectedCount, $haystack, string $message = '')
 * @method void assertAttributeCount(int $expectedCount, string $haystackAttributeName, $haystackClassOrObject, string $message = '')
 * @method void assertNotCount(int $expectedCount, $haystack, string $message = '')
 * @method void assertAttributeNotCount(int $expectedCount, string $haystackAttributeName, $haystackClassOrObject, string $message = '')
 * @method void assertEquals($expected, $actual, string $message = '', float | int | bool $canonicalize = false, bool $ignoreCase = false)
 * @method void assertAttributeEquals($expected, string $actualAttributeName, $actualClassOrObject, string $message = '', float | int | bool $canonicalize = false, bool $ignoreCase = false)
 * @method void assertNotEquals($expected, $actual, string $message = '')
 * @method void assertAttributeNotEquals($expected, string $actualAttributeName, $actualClassOrObject, string $message = '', float | int | bool $canonicalize = false, bool $ignoreCase = false)
 * @method void assertEmpty($actual, string $message = '')
 * @method void assertAttributeEmpty(string $haystackAttributeName, $haystackClassOrObject, string $message = '')
 * @method void assertNotEmpty($actual, string $message = '')
 * @method void assertAttributeNotEmpty(string $haystackAttributeName, $haystackClassOrObject, string $message = '')
 * @method void assertGreaterThan($expected, $actual, string $message = '')
 * @method void assertAttributeGreaterThan($expected, string $actualAttributeName, $actualClassOrObject, string $message = '')
 * @method void assertGreaterThanOrEqual($expected, $actual, string $message = '')
 * @method void assertAttributeGreaterThanOrEqual($expected, string $actualAttributeName, $actualClassOrObject, string $message = '')
 * @method void assertLessThan($expected, $actual, string $message = '')
 * @method void assertAttributeLessThan($expected, string $actualAttributeName, $actualClassOrObject, string $message = '')
 * @method void assertLessThanOrEqual($expected, $actual, string $message = '')
 * @method void assertAttributeLessThanOrEqual($expected, string $actualAttributeName, $actualClassOrObject, string $message = '')
 * @method void assertFileEquals(string $expected, string $actual, string $message = '', bool $canonicalize = false, bool $ignoreCase = false)
 * @method void assertFileNotEquals(string $expected, string $actual, string $message = '', bool $canonicalize = false, bool $ignoreCase = false)
 * @method void assertStringEqualsFile(string $expectedFile, string $actualString, string $message = '', bool $canonicalize = false, bool $ignoreCase = false)
 * @method void assertStringNotEqualsFile(string $expectedFile, string $actualString, string $message = '', bool $canonicalize = false, bool $ignoreCase = false)
 * @method void assertIsReadable(string $filename, string $message = '')
 * @method void assertNotIsReadable(string $filename, string $message = '')
 * @method void assertIsWritable(string $filename, string $message = '')
 * @method void assertNotIsWritable(string $filename, string $message = '')
 * @method void assertDirectoryExists(string $directory, string $message = '')
 * @method void assertDirectoryNotExists(string $directory, string $message = '')
 * @method void assertDirectoryIsReadable(string $directory, string $message = '')
 * @method void assertDirectoryNotIsReadable(string $directory, string $message = '')
 * @method void assertDirectoryIsWritable(string $directory, string $message = '')
 * @method void assertDirectoryNotIsWritable(string $directory, string $message = '')
 * @method void assertFileExists(string $filename, string $message = '')
 * @method void assertFileNotExists(string $filename, string $message = '')
 * @method void assertFileIsReadable(string $file, string $message = '')
 * @method void assertFileNotIsReadable(string $file, string $message = '')
 * @method void assertFileIsWritable(string $file, string $message = '')
 * @method void assertFileNotIsWritable(string $file, string $message = '')
 * @method void assertTrue($condition, string $message = '')
 * @method void assertNotTrue($condition, string $message = '')
 * @method void assertFalse($condition, string $message = '')
 * @method void assertNotFalse($condition, string $message = '')
 * @method void assertNull($actual, string $message = '')
 * @method void assertNotNull($actual, string $message = '')
 * @method void assertFinite($actual, string $message = '')
 * @method void assertInfinite($actual, string $message = '')
 * @method void assertNan($actual, string $message = '')
 * @method void assertClassHasAttribute(string $attributeName, string $className, string $message = '')
 * @method void assertClassNotHasAttribute(string $attributeName, string $className, string $message = '')
 * @method void assertClassHasStaticAttribute(string $attributeName, string $className, string $message = '')
 * @method void assertClassNotHasStaticAttribute(string $attributeName, string $className, string $message = '')
 * @method void assertObjectHasAttribute(string $attributeName, $object, string $message = '')
 * @method void assertObjectNotHasAttribute(string $attributeName, $object, string $message = '')
 * @method void assertSame($expected, $actual, string $message = '')
 * @method void assertAttributeSame($expected, string $actualAttributeName, $actualClassOrObject, string $message = '')
 * @method void assertNotSame($expected, $actual, string $message = '')
 * @method void assertAttributeNotSame($expected, string $actualAttributeName, $actualClassOrObject, string $message = '')
 * @method void assertInstanceOf(string $expected, $actual, string $message = '')
 * @method void assertAttributeInstanceOf(string $expected, string $attributeName, $classOrObject, string $message = '')
 * @method void assertNotInstanceOf(string $expected, $actual, string $message = '')
 * @method void assertAttributeNotInstanceOf(string $expected, string $attributeName, $classOrObject, string $message = '')
 * @method void assertInternalType(string $expected, $actual, string $message = '')
 * @method void assertAttributeInternalType(string $expected, string $attributeName, $classOrObject, string $message = '')
 * @method void assertNotInternalType(string $expected, $actual, string $message = '')
 * @method void assertAttributeNotInternalType(string $expected, string $attributeName, $classOrObject, string $message = '')
 * @method void assertRegExp(string $pattern, string $string, string $message = '')
 * @method void assertNotRegExp(string $pattern, string $string, string $message = '')
 * @method void assertSameSize($expected, $actual, string $message = '')
 * @method void assertNotSameSize($expected, $actual, string $message = '')
 * @method void assertStringMatchesFormat(string $format, string $string, string $message = '')
 * @method void assertStringNotMatchesFormat(string $format, string $string, string $message = '')
 * @method void assertStringMatchesFormatFile(string $formatFile, string $string, string $message = '')
 * @method void assertStringNotMatchesFormatFile(string $formatFile, string $string, string $message = '')
 * @method void assertStringStartsWith(string $prefix, string $string, string $message = '')
 * @method void assertStringStartsNotWith($prefix, $string, string $message = '')
 * @method void assertStringEndsWith(string $suffix, string $string, string $message = '')
 * @method void assertStringEndsNotWith(string $suffix, string $string, string $message = '')
 * @method void assertXmlFileEqualsXmlFile(string $expectedFile, string $actualFile, string $message = '')
 * @method void assertXmlFileNotEqualsXmlFile(string $expectedFile, string $actualFile, string $message = '')
 * @method void assertXmlStringEqualsXmlFile(string $expectedFile, $actualXml, string $message = '')
 * @method void assertXmlStringNotEqualsXmlFile(string $expectedFile, $actualXml, string $message = '')
 * @method void assertXmlStringEqualsXmlString($expectedXml, $actualXml, string $message = '')
 * @method void assertXmlStringNotEqualsXmlString($expectedXml, $actualXml, string $message = '')
 * @method void assertEqualXMLStructure(DOMElement $expectedElement, DOMElement $actualElement, bool $checkAttributes = false, string $message = '')
 * @method void assertThat($value, PHPUnit\Framework\Constraint\Constraint $constraint, string $message = '')
 * @method void assertJson(string $actualJson, string $message = '')
 * @method void assertJsonStringEqualsJsonString(string $expectedJson, string $actualJson, string $message = '')
 * @method void assertJsonStringNotEqualsJsonString($expectedJson, $actualJson, string $message = '')
 * @method void assertJsonStringEqualsJsonFile(string $expectedFile, string $actualJson, string $message = '')
 * @method void assertJsonStringNotEqualsJsonFile(string $expectedFile, string $actualJson, string $message = '')
 * @method void assertJsonFileEqualsJsonFile(string $expectedFile, string $actualFile, string $message = '')
 * @method void assertJsonFileNotEqualsJsonFile(string $expectedFile, string $actualFile, string $message = '')
 * Class TestCase
 * @package Vanilla\Testing
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    private static $app;

    public static function setUpBeforeClass()
    {
        \Vanilla\Config\Environment::load("/php/qyd/haima");
        if (self::$app == null) {
            self::$app = new Application(__DIR__ . '/../../../../../');
        }
    }

    public function get($uri, $options)
    {
        return $this->call(__FUNCTION__, $uri, $options);
    }

    public function post($uri, $options)
    {
        return $this->call(__FUNCTION__, $uri, $options);
    }


    /**
     * @return Response
     */
    private function call($method, $uri, $options)
    {
        $query = $post = $server = [];
        $content = '';

        if (isset($options['json'])) {
            $content = json_encode($options['json']);
        }

        if (isset($options['query'])) {
            $query = $options['query'];
        }

        if (isset($options['headers'])) {

            foreach ($options['headers'] as $key => $value) {
                $key = strtr(strtoupper($key), '-', '_');

                if (substr($key, 0, strlen('HTTP_')) != 'HTTP_' && $key != 'CONTENT_TYPE' && $key != 'REMOTE_ADDR') {
                    $key = 'HTTP_' . $key;
                }
                $server[$key] = $value;
            }
        }

        if (isset($options['json']) && !isset($server['CONTENT_TYPE'])) {
            $server['HTTP_CONTENT_TYPE'] = 'application/json';
            $server['CONTENT_TYPE'] = 'application/json';
        }

        if(in_array(strtoupper($method),['GET','POST','PUT','DELETE','HEAD','PATCH'])){
            $server['REQUEST_METHOD'] = strtoupper($method);
        }

        $server['REQUEST_URI'] = $uri ?? '';

        $request = new Request();
        self::$app['request'] = $request->createRequestFrom($query, $post, [], $server, $content);

        try {
            $response = self::$app->run();
            return $response;
        } catch (\Exception $e) {
            $handler = new \App\Exceptions\Handler();
            return $handler->render(app('request'), $e);
        }
    }
}
