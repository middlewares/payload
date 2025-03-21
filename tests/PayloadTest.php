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
    /**
     * @return array<int, array<int, array<string, string>|SimpleXMLElement|string|null>>
     */
    public function payloadProvider(): array
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
     * @param SimpleXMLElement|array<string, string>|null $result
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
                $this->assertEquals($result, $request->getParsedBody());
                echo 'Ok';
            },
        ], $request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Ok', (string) $response->getBody());
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

    /**
     * @return array<array<string[]|string>>
     */
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
     * @param string[]                  $methods
     * @param array<string,string>|null $result
     */
    public function testMethods(array $methods, string $method, string $body, ?array $result = null): void
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

    public function testOverride(): void
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

    public function testContentType(): void
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

    /**
     * @return array<mixed>
     */
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
     * @param object|array<string,string>|null $expected
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
                    $this->assertEquals($expected, $request->getParsedBody());

                    echo 'Ok';
                },
            ],
            $request
        );

        $this->assertEquals('Ok', (string) $response->getBody());
    }
}
