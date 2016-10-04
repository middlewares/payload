<?php

namespace Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Interop\Http\Middleware\DelegateInterface;
use Psr\Http\Message\StreamInterface;
use Exception;

abstract class Payload
{
    /**
     * @var string
     */
    protected $mimetype;

    /**
     * Process a server request and return a response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if (!$request->getParsedBody()
         && in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE', 'COPY', 'LOCK', 'UNLOCK'], true)
         && stripos($request->getHeaderLine('Content-Type'), $this->mimetype) === 0) {
            try {
                $request = $request->withParsedBody($this->parse($request->getBody()));
            } catch (Exception $exception) {
                return Utils\Factory::createResponse(400);
            }
        }

        return $delegate->process($request);
    }

    /**
     * Parse the body.
     *
     * @param StreamInterface $stream
     *
     * @return array
     */
    abstract protected function parse(StreamInterface $stream);
}
