<?php
declare(strict_types = 1);

namespace Middlewares;

use Interop\Http\Server\MiddlewareInterface;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use SplTempFileObject;

class CsvPayload extends Payload implements MiddlewareInterface
{
    /**
     * @var array
     */
    protected $contentType = ['text/csv'];

    /**
     * The field delimiter (one character only).
     *
     * @var string
     */
    protected $delimiter = ',';

    /**
     * The field enclosure (one character only).
     *
     * @var string
     */
    protected $enclosure = '"';

    /**
     * The field escape (one character only).
     *
     * @var string
     */
    protected $escape = '\\';

    /**
     * Set Csv Control delimiter character
     */
    public function delimiter(string $delimiter): self
    {
        $this->delimiter = self::filterControl($delimiter, 'delimiter');

        return $this;
    }

    /**
     * Set Csv Control enclosure character
     */
    public function enclosure(string $enclosure): self
    {
        $this->enclosure = self::filterControl($enclosure, 'enclosure');

        return $this;
    }

    /**
     * Set Csv Control escape character
     */
    public function escape(string $escape): self
    {
        $this->escape = self::filterControl($escape, 'escape');

        return $this;
    }

    /**
     * Filter Csv control character
     */
    private static function filterControl(string $char, string $type): string
    {
        if (1 == strlen($char)) {
            return $char;
        }

        throw new InvalidArgumentException(sprintf('The %s character must be a single character', $type));
    }

    /**
     * {@inheritdoc}
     */
    protected function parse(StreamInterface $stream): array
    {
        $csv = new SplTempFileObject();
        $csv->fwrite((string) $stream);
        $csv->setFlags(SplTempFileObject::READ_CSV);
        $csv->setCsvControl($this->delimiter, $this->enclosure, $this->escape);

        return iterator_to_array($csv);
    }
}
