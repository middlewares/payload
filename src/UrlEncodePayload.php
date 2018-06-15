<?php
declare(strict_types=1);

namespace Middlewares;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;

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
