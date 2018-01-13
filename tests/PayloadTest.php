<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use InvalidArgumentException;
use Middlewares\CsvPayload;
use Middlewares\JsonPayload;
use Middlewares\UrlEncodePayload;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class JsonPayloadTest extends TestCase
{
    public function payloadProvider()
    {
        return [
            ['application/json', '{"bar":"foo"}', ['bar' => 'foo']],
            ['application/json', '', []],
            ['application/x-www-form-urlencoded', 'bar=foo', ['bar' => 'foo']],
            ['application/x-www-form-urlencoded', '', []],
            ['text/csv', "one,two\nthree,four", [['one', 'two'], ['three', 'four']]],
        ];
    }

    /**
     * @dataProvider payloadProvider
     */
    public function testPayload(string $header, string $body, array $result)
    {
        $request = Factory::createServerRequest([], 'POST')
            ->withHeader('Content-Type', $header);

        $request->getBody()->write($body);

        $response = Dispatcher::run([
            new JsonPayload(),
            new CsvPayload(),
            new UrlEncodePayload(),
            function ($request) use ($result) {
                $this->assertEquals($result, $request->getParsedBody());
                echo 'Ok';
            },
        ], $request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testError()
    {
        $request = Factory::createServerRequest([], 'POST')
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write('{invalid:"json"}');

        $response = Dispatcher::run([
            new JsonPayload(),
        ], $request);

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function methodProvider()
    {
        return [
            [
                ['POST'],
                'POST',
                '{"bar":"foo"}',
                ['bar' => 'foo'],
            ], [
                ['PUT'],
                'POST',
                '{"bar":"foo"}',
            ], [
                ['GET'],
                'GET',
                '{"bar":"foo"}',
                ['bar' => 'foo'],
            ],
        ];
    }

    /**
     * @dataProvider methodProvider
     */
    public function testMethods(array $methods, string $method, string $body, array $result = null)
    {
        $request = Factory::createServerRequest([], $method)
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write($body);

        $response = Dispatcher::run([
            (new JsonPayload())->methods($methods),
            function ($request) use ($result) {
                $this->assertEquals($result, $request->getParsedBody());

                echo 'Ok';
            },
        ], $request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testOverride()
    {
        $request = Factory::createServerRequest([], 'POST')
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write('{"bar":"foo"}');

        $response = Dispatcher::run([
            new JsonPayload(),
            function ($request, $next) {
                $this->assertEquals(['bar' => 'foo'], $request->getParsedBody());

                return $next->handle($request->withParsedBody(['other' => 'body']));
            },
            new JsonPayload(),
            function ($request, $next) {
                $this->assertEquals(['other' => 'body'], $request->getParsedBody());

                return $next->handle($request);
            },
            (new JsonPayload())->override(),
            function ($request, $next) {
                $this->assertEquals(['bar' => 'foo'], $request->getParsedBody());

                echo 'Ok';
            },
        ], $request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testContentType()
    {
        $request = Factory::createServerRequest([], 'POST')
            ->withHeader('Content-Type', 'bar/foo; charset=utf8');

        $request->getBody()->write('{"bar":"foo"}');

        $response = Dispatcher::run([
            (new JsonPayload())->contentType(['application/json', 'bar/foo']),
            function ($request, $next) {
                $this->assertEquals(['bar' => 'foo'], $request->getParsedBody());

                echo 'Ok';
            },
        ], $request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testCsvPayloadOptions()
    {
        $csv_payload = (new CsvPayload())
            ->delimiter(';')
            ->enclosure('"')
            ->escape('\\');

        $request = Factory::createServerRequest([], 'POST')
            ->withHeader('Content-Type', 'text/csv');

        $request->getBody()->write("one;two\nthree;four");

        $response = Dispatcher::run([
            $csv_payload,
            function ($request) {
                $this->assertEquals([['one', 'two'], ['three', 'four']], $request->getParsedBody());
                echo 'Ok';
            },
        ], $request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Ok', (string) $response->getBody());
    }

    /**
     * @dataProvider invalidCsvControlProvider
     */
    public function testCsvPayloadSettersThrowsException(string $char)
    {
        $this->expectException(InvalidArgumentException::class);
        (new CsvPayload())->delimiter($char);
    }

    public function invalidCsvControlProvider(): array
    {
        return [
            'too long' => ['coucou'],
            'too short' => [''],
            'unicode char' => ['💩'],
            'unicode char PHP7 notation' => ["\u{0001F4A9}"],
        ];
    }

    public function testJsonOptions()
    {
        $request = Factory::createServerRequest([], 'POST')
            ->withHeader('Content-Type', 'application/json');

        $body = <<<'EOT'
[
    12345678901234567890123456789012345678901234567890123456789012345678901234567890,
    {
        "level1": {
            "level2": {
                "level3": "value"
            }
        }
    }
]
EOT;

        $request->getBody()->write($body);

        $response = Dispatcher::run(
            [
                (new JsonPayload())
                    ->associative(false)
                    ->depth(1)
                    ->options(JSON_BIGINT_AS_STRING),
            ],
            $request
        );

        $this->assertEquals(400, $response->getStatusCode());
    }
}
