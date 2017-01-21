<?php

namespace Middlewares;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use SplTempFileObject;

class CsvPayload extends Payload implements MiddlewareInterface
{
    /**
     * @var string
     */
    protected $contentType = 'text/csv';

    /**
     * The field delimiter (one character only).
     *
     * @var string
     */
    protected $delimiter = ",";

    /**
     * The field enclosure (one character only).
     *
     * @var string
     */
    protected $enclosure = "\"";

    /**
     * The field escape (one character only).
     *
     * @var string
     */
    protected $escape = "\\";

    /**
     * Set Csv Control delimiter character
     *
     * @param string $enclosure
     *
     * @return self
     */
    public function delimiter($delimiter)
    {
        $this->delimiter = self::filterControl($delimiter, 'delimiter');

        return $this;
    }

    /**
     * Set Csv Control enclosure character
     *
     * @param string $enclosure
     *
     * @return self
     */
    public function enclosure($enclosure)
    {
        $this->enclosure = self::filterControl($enclosure, 'enclosure');

        return $this;
    }

    /**
     * Set Csv Control escape character
     *
     * @param string $enclosure
     *
     * @return self
     */
    public function escape($escape)
    {
        $this->escape = self::filterControl($escape, 'escape');

        return $this;
    }

    /**
     * Filter Csv control character
     *
     * @param string $char Csv control character
     * @param string $type Csv control character type
     *
     * @throws InvalidArgumentException If the Csv control character is not one character only.
     *
     * @return string
     */
    private static function filterControl($char, $type)
    {
        if (1 == strlen($char)) {
            return $char;
        }

        throw new InvalidArgumentException(sprintf('The %s character must be a single character', $type));
    }

    /**
     * {@inheritdoc}
     */
    protected function parse(StreamInterface $stream)
    {
        $csv = new SplTempFileObject();
        $csv->fwrite((string) $stream);
        $csv->setFlags(SplTempFileObject::READ_CSV);
        $csv->setCsvControl($this->delimiter, $this->enclosure, $this->escape);

        return iterator_to_array($csv);
    }
}
