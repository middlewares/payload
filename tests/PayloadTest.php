<?php

namespace Middlewares\Tests;

use Middlewares\JsonPayload;
use Middlewares\CsvPayload;
use Middlewares\UrlEncodePayload;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;

class JsonPayloadTest extends \PHPUnit_Framework_TestCase
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
    public function testPayload($header, $body, $result)
    {
        $request = Factory::createServerRequest([], 'POST')
            ->withHeader('Content-Type', $header);

        $request->getBody()->write($body);

        $response = (new Dispatcher([
            new JsonPayload(),
            new CsvPayload(),
            new UrlEncodePayload(),
            function ($request) use ($result) {
                $this->assertEquals($result, $request->getParsedBody());
                echo 'Ok';
            },
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testError()
    {
        $request = Factory::createServerRequest([], 'POST')
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write('{invalid:"json"}');

        $response = (new Dispatcher([
            new JsonPayload(),
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
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
                null,
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
    public function testMethods($methods, $method, $body, $result)
    {
        $request = Factory::createServerRequest([], $method)
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write($body);

        $response = (new Dispatcher([
            (new JsonPayload())->methods($methods),
            function ($request) use ($result) {
                $this->assertEquals($result, $request->getParsedBody());

                echo 'Ok';
            },
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testOverride()
    {
        $request = Factory::createServerRequest([], 'POST')
            ->withHeader('Content-Type', 'application/json');

        $request->getBody()->write('{"bar":"foo"}');

        $response = (new Dispatcher([
            new JsonPayload(),
            function ($request, $next) {
                $this->assertEquals(['bar' => 'foo'], $request->getParsedBody());

                return $next->process($request->withParsedBody(['other' => 'body']));
            },
            new JsonPayload(),
            function ($request, $next) {
                $this->assertEquals(['other' => 'body'], $request->getParsedBody());

                return $next->process($request);
            },
            (new JsonPayload())->override(),
            function ($request, $next) {
                $this->assertEquals(['bar' => 'foo'], $request->getParsedBody());

                echo 'Ok';
            },
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Ok', (string) $response->getBody());
    }
}
