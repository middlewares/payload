<?php

namespace Middlewares\Tests;

use Middlewares\JsonPayload;
use Middlewares\CsvPayload;
use Middlewares\UrlEncodePayload;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;
use mindplay\middleman\Dispatcher;

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
        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write($body);

        $request = (new ServerRequest())
            ->withHeader('Content-Type', $header)
            ->withMethod('POST')
            ->withBody($stream);

        $response = (new Dispatcher([
            new JsonPayload(),
            new CsvPayload(),
            new UrlEncodePayload(),
            function ($request) use ($result) {
                $this->assertEquals($result, $request->getParsedBody());
                $response = new Response();
                $response->getBody()->write('Ok');

                return $response;
            },
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Ok', (string) $response->getBody());
    }

    public function testError()
    {
        $stream = new Stream(fopen('php://temp', 'r+'));
        $stream->write('{invalid:"json"}');

        $request = (new ServerRequest())
            ->withHeader('Content-Type', 'application/json')
            ->withMethod('POST')
            ->withBody($stream);

        $response = (new Dispatcher([
            new JsonPayload(),
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals(400, $response->getStatusCode());
    }
}
