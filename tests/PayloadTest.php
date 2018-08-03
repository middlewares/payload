<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Middlewares\JsonPayload;
use Middlewares\UrlEncodePayload;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use Middlewares\Utils\HttpErrorException;
use PHPUnit\Framework\TestCase;

class PayloadTest extends TestCase
{
    public function payloadProvider()
    {
        return [
            ['application/json', '{"bar":"foo"}', ['bar' => 'foo']],
            ['application/json', '', []],
            ['application/x-www-form-urlencoded', 'bar=foo', ['bar' => 'foo']],
            ['application/x-www-form-urlencoded', '', []],
        ];
    }

    /**
     * @dataProvider payloadProvider
     */
    public function testPayload(string $header, string $body, array $result)
    {
        $request = Factory::createServerRequest('POST', '/')
            ->withHeader('Content-Type', $header);

        $request->getBody()->write($body);

        $response = Dispatcher::run([
            new JsonPayload(),
            new UrlEncodePayload(),
            function ($request) use ($result) {
                $this->assertEquals($result, $request->getParsedBody());
                echo 'Ok';
            },
        ], $request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testJsonError()
    {
        $this->expectException(HttpErrorException::class);
        $this->expectExceptionCode(400);

        $request = Factory::createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write('{invalid:"json"}');

        $response = Dispatcher::run([
            new JsonPayload(),
        ], $request);
    }

    public function testUrlEncodeError()
    {
        $this->expectException(HttpErrorException::class);
        $this->expectExceptionCode(400);

        $request = Factory::createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        $request->getBody()->write('&=');

        $response = Dispatcher::run([
            new UrlEncodePayload(),
        ], $request);
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
        $request = Factory::createServerRequest($method, '/')
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
        $request = Factory::createServerRequest('POST', '/')
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
        $request = Factory::createServerRequest('POST', '/')
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

    public function testJsonOptions()
    {
        $this->expectException(HttpErrorException::class);
        $this->expectExceptionCode(400);

        $request = Factory::createServerRequest('POST', '/')
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
    }
}
