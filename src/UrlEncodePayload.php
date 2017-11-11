<?php
declare(strict_types = 1);

namespace Middlewares;

use Interop\Http\Server\MiddlewareInterface;
use Psr\Http\Message\StreamInterface;

class UrlEncodePayload extends Payload implements MiddlewareInterface
{
    /**
     * @var array
     */
    protected $contentType = ['application/x-www-form-urlencoded'];

    /**
     * {@inheritdoc}
     */
    protected function parse(StreamInterface $stream): array
    {
        parse_str((string) $stream, $data);

        return $data ?: [];
    }
}
