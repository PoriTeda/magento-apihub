<?php
namespace Riki\Questionnaire\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class DataType
 * @package Riki\CedynaInvoice\Model\Source\Config
 */
class EnqueteType extends \Magento\Framework\DataObject implements OptionSourceInterface
{
    const CHECKOUT = 0;
    const DISENGAGEMENT = 1;

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = ['value' => self::CHECKOUT, 'label' => __('Checkout Questionnaire')];
        $options[] = ['value' => self::DISENGAGEMENT, 'label' => __('Disengagement Questionnaire')];
        return $options;
    }
}
