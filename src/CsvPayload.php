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
    protected $mimetype = 'text/csv';

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
     * @var bool
     */
    private $assoc = true;

    /**
     * Configure the returned object to be converted into
     *  - a sequential array of all CSV lines
     *  - or a SplTempFileObject
     *
     * @param bool $assoc
     *
     * @return self
     */
    public function associative($assoc = true)
    {
        $this->assoc = (bool) $assoc;

        return $this;
    }

    /**
     * Set Csv Control characters
     *
     * Mimic SplFileObject::setCsvControl
     *
     * @see http://be2.php.net/manual/en/splfileobject.setcsvcontrol.php
     *
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     *
     * @return self
     */
    public function setCsvControl($delimiter = ",", $enclosure = "\"", $escape = "\\")
    {
        $this->delimiter = $this->filterControl($delimiter, 'delimiter');
        $this->enclosure = $this->filterControl($enclosure, 'enclosure');
        $this->escape = $this->filterControl($escape, 'escape');

        return $this;
    }

    /**
     * Filter Csv control character
     *
     * @param  string $char Csv control character
     * @param  string $type Csv control character type
     *
     * @throws  InvalidArgumentException If the Csv control character is not one character only.
     *
     * @return string
     */
    protected function filterControl($char, $type)
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

        if ($this->assoc) {
            return iterator_to_array($csv);
        }

        return $csv;
    }
}
