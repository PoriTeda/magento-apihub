<?php
namespace Riki\Sales\Model\ResourceModel\Sales\Grid;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class PaymentStatus
 * @package Riki\Sales\Model\ResourceModel\Sales\Grid
 */
class PaymentStatus extends \Magento\Framework\DataObject implements OptionSourceInterface
{
    const PAYMENT_NOT_APPLICABLE = 'not_applicable';
    const PAYMENT_COLLECTED = 'payment_collected';
    const PAYMENT_NESTLE_RECEIVED = 'nestle_payment_received';

    /**
     * PaymentStatus constructor.
     * @param array $data
     */
    public function __construct(
        array $data = []

    ) {
        parent::__construct($data);
    }

    /**
     * @return array
     */
    public static function getOptionArray()
    {
        return [
            self::PAYMENT_NOT_APPLICABLE => __('Not applicable'),
            self::PAYMENT_COLLECTED => __('Payment collected'),
            self::PAYMENT_NESTLE_RECEIVED => __('Nestle payment received'),
        ];
    }
    /**
     * Retrieve all options
     *
     * @return array
     */
    public static function getAllOption()
    {
        $options = self::getOptionArray();
        array_unshift($options, ['value' => '', 'label' => '']);
        return $options;
    }

    /**
     * Retrieve all options
     *
     * @return array
     */
    public static function getAllOptions()
    {
        $res = [];
        foreach (self::getOptionArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
        }
        return $res;
    }

    /**
     * Retrieve option text
     *
     * @param int $optionId
     * @return string
     */
    public static function getOptionText($optionId)
    {
        $options = self::getOptionArray();
        return isset($options[$optionId]) ? $options[$optionId] : null;
    }

    /**
     * @return array
     */

    public function toOptionArray()
    {
        return $this->getAllOptions();
    }
}
