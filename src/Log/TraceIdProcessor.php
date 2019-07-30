<?php


namespace Vanilla\Log;

use Monolog\Processor\ProcessorInterface;

class TraceIdProcessor implements ProcessorInterface
{
    public function __invoke(array $record)
    {
        $record['extra']['traceId'] = uuid('v4', true);
        return $record;
    }

}
