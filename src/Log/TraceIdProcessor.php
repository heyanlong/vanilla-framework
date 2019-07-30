<?php


namespace Vanilla\Log;

use Monolog\Processor\ProcessorInterface;

class TraceIdProcessor implements ProcessorInterface
{
    public function __invoke(array $record)
    {
        $record['extra']['traceId'] = traceId();
        return $record;
    }

}
