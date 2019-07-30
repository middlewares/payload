<?php
declare(strict_types = 1);

namespace Middlewares;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;

class XmlPayload extends Payload implements MiddlewareInterface
{
    /**
     * @var array
     */
    protected $contentType = ['text/xml', 'application/xml', 'application/x-xml'];

    private $associative = true;

    /**
     * Configure the returned object to be converted into an array instead of an object.
     */
    public function associative(bool $associative = true): self
    {
        $this->associative = $associative;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function parse(StreamInterface $stream)
    {
        $xml = trim((string)$stream);

        if ($xml === '') {
            return $this->associative ? [] : null;
        }

        $xml = simplexml_load_string($xml);
        $json = json_encode($xml);
        $array = json_decode($json, $this->associative);

        return $array;
    }
}
