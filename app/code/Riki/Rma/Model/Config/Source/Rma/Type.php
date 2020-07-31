<?php
namespace Riki\Rma\Model\Config\Source\Rma;

class Type extends \Riki\Framework\Model\Source\AbstractOption implements \Riki\Rma\Api\Data\Rma\TypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel($option)
    {
        switch ($option) {
            case self::FULL:
                return __('Full');

            case self:: PARTIAL:
                return __('Partial');
        }

        return parent::getLabel($option);
    }
}