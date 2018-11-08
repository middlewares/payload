<?php
declare(strict_types = 1);

namespace Middlewares;

use Exception;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\MiddlewareInterface;

class JsonPayload extends Payload implements MiddlewareInterface
{
    /**
     * @var array
     */
    protected $contentType = ['application/json'];

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
     * Configure the returned object to be converted into an array instead of an object.
     *
     * @see http://php.net/manual/en/function.json-decode.php
     */
    public function associative(bool $associative = true): self
    {
        $this->associative = $associative;

        return $this;
    }

    /**
     * Configure the recursion depth.
     *
     * @see http://php.net/manual/en/function.json-decode.php
     */
    public function depth(int $depth): self
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Configure the decode options.
     *
     * @see http://php.net/manual/en/function.json-decode.php
     */
    public function options(int $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function parse(StreamInterface $stream)
    {
        $json = trim((string) $stream);

        if ($json === '') {
            return $this->associative ? [] : null;
        }

        $data = json_decode($json, $this->associative, $this->depth, $this->options);
        $code = json_last_error();

        if ($code !== JSON_ERROR_NONE) {
            // This can be modified for PHP 7.3 when it is stable:
            // https://ayesh.me/Upgrade-PHP-7.3#json-exceptions
            throw new Exception(sprintf('JSON: %s', json_last_error_msg()), $code);
        }

        if ($this->associative) {
            return $data ?: [];
        }

        return $data;
    }
}
