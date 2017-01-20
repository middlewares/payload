<?php

namespace Middlewares;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\StreamInterface;
use DomainException;

class JsonPayload extends Payload implements MiddlewareInterface
{
    /**
     * @var string
     */
    protected $mimetype = 'application/json';

    /**
     * @var bool
     */
    private $associative = true;

    /**
     * @var int
     */
    private $depth = 512;

    /**
     * @var int
     */
    private $options = 0;

    /**
     * Configure the returned object to be converted into a sequential array of all CSV lines
     * or a SplTempFileObject
     *
     * @param bool $assoc
     *
     * @return self
     */
    public function associative($assoc = true)
    {
        $this->associative = $associative;

        return $this;
    }

    /**
     * Configure the recursion depth.
     *
     * @see http://php.net/manual/en/function.json-decode.php
     *
     * @param int $depth
     *
     * @return self
     */
    public function depth($depth)
    {
        $this->depth = (int) $depth;

        return $this;
    }

    /**
     * Configure the decode options.
     *
     * @see http://php.net/manual/en/function.json-decode.php
     *
     * @param int $options
     *
     * @return self
     */
    public function options($options)
    {
        $this->options = (int) $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function parse(StreamInterface $stream)
    {
        $json = trim((string) $stream);

        if ($json === '') {
            return [];
        }

        $data = json_decode($json, $this->associative, $this->depth, $this->options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new DomainException(json_last_error_msg());
        }

        return $data ?: [];
    }
}
