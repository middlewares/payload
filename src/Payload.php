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
     * @var bool
     */
    protected $override = false;

    /**
     * @var string[]
     */
    protected $methods = ['POST', 'PUT', 'PATCH', 'DELETE', 'COPY', 'LOCK', 'UNLOCK'];

    /**
     * Configure the methods allowed.
     *
     * @param string[] $methods
     *
     * @return self
     */
    public function methods(array $methods)
    {
        $this->methods = $methods;

        return $this;
    }

    /**
     * Configure if the parsed body overrides the previous value.
     *
     * @param bool $override
     *
     * @return self
     */
    public function override($override = true)
    {
        $this->override = (bool) $override;

        return $this;
    }

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
        if ((!$request->getParsedBody() || $this->override)
         && in_array($request->getMethod(), $this->methods, true)
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
