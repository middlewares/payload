<?php

namespace Middlewares;

use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\StreamInterface;

class UrlEncodePayload extends Payload implements ServerMiddlewareInterface
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
