<?php
namespace Riki\Sales\Model\ResourceModel\Sales\Grid;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class PaymentStatus
 * @package Riki\Sales\Model\ResourceModel\Sales\Grid
 */
class OrderStatus extends \Magento\Framework\DataObject implements OptionSourceInterface
{
    const STATUS_ORDER_CANCELED = 'canceled';
    const STATUS_ORDER_CAPTURE_FAILED = 'capture_failed';
    const STATUS_ORDER_COMPLETE = 'complete';
    const STATUS_ORDER_CRD_FEEDBACK = 'feedback_crd';
    const STATUS_ORDER_SUSPECTED_FRAUD = 'fraud';
    const STATUS_ORDER_PENDING_CRD_REVIEW = 'holded';
    const STATUS_ORDER_PARTIALLY_SHIPPED = 'partially_shipped';
    const STATUS_ORDER_PENDING_CVS = 'pending_cvs_payment';
    const STATUS_ORDER_PENDING_CC = 'pending_payment';
    const STATUS_ORDER_IN_PROCESSING = 'preparing_for_shipping';
    const STATUS_ORDER_SHIPPED_ALL = 'shipped_all';
    const STATUS_ORDER_SUSPICIOUS = 'suspicious';
    const STATUS_ORDER_NOT_SHIPPED = 'waiting_for_shipping';


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
            self::STATUS_ORDER_CANCELED => __('CANCELED'),
            self::STATUS_ORDER_CAPTURE_FAILED => __('CAPTURE_FAILED'),
            self::STATUS_ORDER_COMPLETE => __('COMPLETE'),
            self::STATUS_ORDER_CRD_FEEDBACK => __('CRD_FEEDBACK'),
            self::STATUS_ORDER_SUSPECTED_FRAUD => __('SUSPECTED_FRAUD'),
            self::STATUS_ORDER_PENDING_CRD_REVIEW => __('PENDING_CRD_REVIEW'),
            self::STATUS_ORDER_PARTIALLY_SHIPPED => __('PARTIALLY_SHIPPED'),
            self::STATUS_ORDER_PENDING_CVS => __('PENDING_CVS'),
            self::STATUS_ORDER_PENDING_CC => __('PENDING_CC'),
            self::STATUS_ORDER_IN_PROCESSING => __('IN_PROCESSING'),
            self::STATUS_ORDER_SHIPPED_ALL => __('SHIPPED_ALL'),
            self::STATUS_ORDER_SUSPICIOUS => __('SUSPICIOUS'),
            self::STATUS_ORDER_NOT_SHIPPED => __('NOT_SHIPPED')
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
