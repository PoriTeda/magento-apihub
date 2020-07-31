<?php

namespace Riki\Rma\Model\Config\Source\ReviewCc\Item;

class Status extends \Riki\Rma\Model\Config\Source\ReviewCc\Status
{
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;

    /**
     * @return array
     */
    public function getOptions()
    {
        return [
            self::STATUS_SUCCESS    =>  __('Success'),
            self::STATUS_FAILED    =>  __('Failed'),
        ];
    }
}
