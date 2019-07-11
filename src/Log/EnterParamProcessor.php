<?php


namespace Vanilla\Log;

use Monolog\Processor\ProcessorInterface;

class EnterParamProcessor implements ProcessorInterface
{
    public function __invoke(array $record)
    {
        $enterParam = file_get_contents('php://input') . json_encode($_GET);
        $record['extra']['enterParam'] = $enterParam;
        return $record;
    }

}
