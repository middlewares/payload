<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Middlewares\JsonPayload;
use Middlewares\UrlEncodePayload;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use Middlewares\Utils\HttpErrorException;
use Middlewares\XmlPayload;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class PayloadTest extends TestCase
{
    /** @return array<array> */
    public function payloadProvider()
    {
        return [
            ['application/json', '{"bar":"foo"}', ['bar' => 'foo']],
            ['application/json', '', []],
            ['application/x-www-form-urlencoded', 'bar=foo', ['bar' => 'foo']],
            ['application/x-www-form-urlencoded', '', []],
            ['application/xml', '<root><bar>foo</bar></root>', new SimpleXMLElement('<root><bar>foo</bar></root>')],
            ['application/xml', '', null],
        ];
    }

    /**
     * @dataProvider payloadProvider
     * @param mixed $result
     */
    public function testPayload(string $header, string $body, $result): void
    {
        $request = Factory::createServerRequest('POST', '/')
            ->withHeader('Content-Type', $header);

        $request->getBody()->write($body);

        $response = Dispatcher::run([
            new JsonPayload(),
            new UrlEncodePayload(),
            new XmlPayload(),
            function ($request) use ($result) {
                self::assertEquals($result, $request->getParsedBody());
                echo 'Ok';
            },
        ], $request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Ok', (string) $response->getBody());
    }

    public function testJsonError(): void
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

    public function testUrlEncodeError(): void
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

    public function testXmlError(): void
    {
        $this->expectException(HttpErrorException::class);
        $this->expectExceptionCode(400);

        $request = Factory::createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/xml');

        $request->getBody()->write('<invalid></xml>');

        $response = Dispatcher::run([
            new XmlPayload(),
        ], $request);
    }

    public function methodProvider(): array
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
    public function testMethods(array $methods, string $method, string $body, array $result = null): void
    {
        $request = Factory::createServerRequest($method, '/')
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write($body);

        $response = Dispatcher::run([
            (new JsonPayload())->methods($methods),
            function ($request) use ($result) {
                self::assertEquals($result, $request->getParsedBody());

                echo 'Ok';
            },
        ], $request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Ok', (string) $response->getBody());
    }

    public function testOverride(): void
    {
        $request = Factory::createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write('{"bar":"foo"}');

        $response = Dispatcher::run([
            new JsonPayload(),
            function ($request, $next) {
                self::assertEquals(['bar' => 'foo'], $request->getParsedBody());

                return $next->handle($request->withParsedBody(['other' => 'body']));
            },
            new JsonPayload(),
            function ($request, $next) {
                self::assertEquals(['other' => 'body'], $request->getParsedBody());

                return $next->handle($request);
            },
            (new JsonPayload())->override(),
            function ($request, $next) {
                self::assertEquals(['bar' => 'foo'], $request->getParsedBody());

                echo 'Ok';
            },
        ], $request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Ok', (string) $response->getBody());
    }

    public function testContentType(): void
    {
        $request = Factory::createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'bar/foo; charset=utf8');

        $request->getBody()->write('{"bar":"foo"}');

        $response = Dispatcher::run([
            (new JsonPayload())->contentType(['application/json', 'bar/foo']),
            function ($request, $next) {
                self::assertEquals(['bar' => 'foo'], $request->getParsedBody());

                echo 'Ok';
            },
        ], $request);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('Ok', (string) $response->getBody());
    }

    public function testJsonOptions(): void
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

    public function jsonDisabledAssociativeProvider(): array
    {
        return [
            ['{}', (object) []],
            ['{"foo": "bar"}', (object) ['foo' => 'bar']],
            ['["foo", "bar"]', ['foo', 'bar']],
            ['', null],
        ];
    }

    /**
     * @dataProvider jsonDisabledAssociativeProvider
     * @param mixed $expected
     */
    public function testJsonAssociativeDisabled(string $body, $expected): void
    {
        $request = Factory::createServerRequest('POST', '/')
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write($body);

        $response = Dispatcher::run(
            [
                (new JsonPayload())->associative(false),
                function ($request) use ($expected) {
                    self::assertEquals($expected, $request->getParsedBody());

                    echo 'Ok';
                },
            ],
            $request
        );

        self::assertEquals('Ok', (string) $response->getBody());
    }
}
