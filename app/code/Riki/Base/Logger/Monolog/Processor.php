<?php

namespace Riki\Base\Logger\Monolog;

class Processor
{
    /**
     * @param $record
     * @return array
     */
    public static function addFormatDateProcessor($record)
    {
        if(
            is_array($record) &&
            isset($record['datetime']) &&
            $record['datetime'] instanceof \DateTime
        ){
            $record['datetime'] = $record['datetime']->format('Y-m-d H:i:s T');
        }

        return $record;
    }
}
