<?php
namespace Riki\Rma\Model\Config\Source\SalesRule;

class IgnoreWarningRma extends \Riki\Framework\Model\Source\AbstractOption implements \Riki\Rma\Api\Data\SalesRule\IgnoreWarningRmaInterface
{
    /**
     * {@inheritdoc}
     *
     * @param string $option
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getLabel($option)
    {
        switch ($option) {
            case self::NO:
                return __('No');

            case self::YES:
                return __('Yes');
        }

        return parent::getLabel($option);
    }
}