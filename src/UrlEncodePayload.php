<?php

namespace Middlewares;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\StreamInterface;

class UrlEncodePayload extends Payload implements MiddlewareInterface
{
    /**
     * @var string
     */
    protected $mimetype = 'application/x-www-form-urlencoded';

    /**
     * {@inheritdoc}
     */
    protected function parse(StreamInterface $stream)
    {
        parse_str((string) $stream, $data);

        return $data ?: [];
    }
}
