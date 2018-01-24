<?php
declare(strict_types = 1);

namespace Middlewares;

use Exception;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

abstract class Payload
{
    /**
     * @var array
     */
    protected $contentType;

    /**
     * @var bool
     */
    protected $override = false;

    /**
     * @var string[]
     */
    protected $methods = ['POST', 'PUT', 'PATCH', 'DELETE', 'COPY', 'LOCK', 'UNLOCK'];

    /**
     * Configure the Content-Type.
     *
     * @param array $contentType
     *
     * @return self
     */
    public function contentType(array $contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

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
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->checkRequest($request)) {
            try {
                $request = $request->withParsedBody($this->parse($request->getBody()));
            } catch (Exception $exception) {
                return Utils\Factory::createResponse(400);
            }
        }

        return $handler->handle($request);
    }

    /**
     * Parse the body.
     *
     * @param StreamInterface $stream
     *
     * @return array
     */
    abstract protected function parse(StreamInterface $stream): array;

    /**
     * Check whether the request payload need to be processed
     *
     * @param ServerRequestInterface $request
     *
     * @return bool
     */
    private function checkRequest(ServerRequestInterface $request)
    {
        if ($request->getParsedBody() && !$this->override) {
            return false;
        }

        if (!in_array($request->getMethod(), $this->methods, true)) {
            return false;
        }

        $contentType = $request->getHeaderLine('Content-Type');

        foreach ($this->contentType as $allowedType) {
            if (stripos($contentType, $allowedType) === 0) {
                return true;
            }
        }

        return false;
    }
}
