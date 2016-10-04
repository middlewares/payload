<?php

namespace Middlewares;

use Interop\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\StreamInterface;

class CsvPayload extends Payload implements ServerMiddlewareInterface
{
    /**
     * @var string
     */
    protected $mimetype = 'text/csv';

    /**
     * {@inheritdoc}
     */
    protected function parse(StreamInterface $stream)
    {
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $resource = $stream->detach();
        $data = [];

        while (($row = fgetcsv($resource)) !== false) {
            $data[] = $row;
        }

        fclose($resource);

        return $data;
    }
}
