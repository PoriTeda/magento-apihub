<?php

namespace Riki\Rma\Model\Config\Source\Reason;

class Dueto extends \Riki\Framework\Model\Source\AbstractOption implements \Riki\Rma\Api\Data\Reason\DuetoInterface
{
    /**
     * @param $value
     * @return \Magento\Framework\Phrase|string
     */
    public function getLabel($value)
    {
        switch ($value) {
            case self::NESTLE:
                return __('Nestle');

            case self::CONSUMER:
                return __('Consumer');
        }

        return parent::getLabel($value);
    }
}
