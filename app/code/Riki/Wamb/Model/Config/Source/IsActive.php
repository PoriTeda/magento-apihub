<?php
namespace Riki\Wamb\Model\Config\Source;

class IsActive extends \Riki\Framework\Model\Source\AbstractOption implements \Riki\Wamb\Api\Data\Rule\IsActiveInterface
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
            case self::IS_ACTIVE:
                return __('Enabled');

            case self::IS_INACTIVE;
                return __('Disabled');
        }

        return parent::getLabel($option);
    }
}