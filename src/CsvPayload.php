<?php

namespace Middlewares;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\StreamInterface;

class CsvPayload extends Payload implements MiddlewareInterface
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
