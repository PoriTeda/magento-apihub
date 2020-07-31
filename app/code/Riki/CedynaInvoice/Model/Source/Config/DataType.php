<?php
namespace Riki\CedynaInvoice\Model\Source\Config;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class DataType
 * @package Riki\CedynaInvoice\Model\Source\Config
 */
class DataType extends \Magento\Framework\DataObject implements OptionSourceInterface
{
    const DATA_TYPE_OPTION_SALES = '01';
    const DATA_TYPE_OPTION_RETURN = '02';
    const DATA_TYPE_OPTION_DISCOUNT = '03';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = ['value' => self::DATA_TYPE_OPTION_SALES, 'label' => __('Sales')];
        $options[] = ['value' => self::DATA_TYPE_OPTION_RETURN, 'label' => __('Return')];
        $options[] = ['value' => self::DATA_TYPE_OPTION_DISCOUNT, 'label' => __('Discount')];
        return $options;
    }
}
