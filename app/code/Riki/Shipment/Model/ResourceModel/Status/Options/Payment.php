<?php
/**
 * Payment Status
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Shipment\Model\ResourceModel\Status\Options
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Shipment\Model\ResourceModel\Status\Options;
use Magento\Framework\Data\OptionSourceInterface;
/**
 * Class Payment
 *
 * @category  RIKI
 * @package   Riki\Shipment\Model\ResourceModel\Status\Options
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class  Payment implements OptionSourceInterface
{
    const SHIPPING_PAYMENT_STATUS_NULL = '';
    const SHIPPING_PAYMENT_STATUS_NOT_APPLICABLE = 'not_applicable';
    const SHIPPING_PAYMENT_STATUS_AUTHORIZED = 'authorized';
    const SHIPPING_PAYMENT_STATUS_SUSPECTED_FRAUD = 'suspected_fraud';
    const SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED = 'payment_collected';
    const SHIPPING_PAYMENT_STATUS_NESTLE_COLLECTED = 'nestle_payment_received';
    const SHIPPING_PAYMENT_STATUS_CAPTURE_FAILED = 'capture_failed';

    const SHIPPING_PAYMENT_STATUS_AUTHORIZED_FAILED = \Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus::PAYMENT_AUTHORIZED_FAILED;

    /**
     * @return array
     */
    public static function getOptionArray()
    {
        return [
            self::SHIPPING_PAYMENT_STATUS_NOT_APPLICABLE => __('Not applicable'),
            self::SHIPPING_PAYMENT_STATUS_AUTHORIZED => __('Authorized'),
            self::SHIPPING_PAYMENT_STATUS_AUTHORIZED_FAILED => __('Authorized failed'),
            self::SHIPPING_PAYMENT_STATUS_SUSPECTED_FRAUD => __('Suspected fraud'),
            self::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED => __('Payment collected'),
            self::SHIPPING_PAYMENT_STATUS_CAPTURE_FAILED => __('Capture failed')
        ];
    }

    public static function getPaymentStatusByMethod( $paymentMethod = false )
    {
        $option = [
            self::SHIPPING_PAYMENT_STATUS_NULL => '',
            self::SHIPPING_PAYMENT_STATUS_NOT_APPLICABLE => __('Not applicable'),
            self::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED => __('Payment collected'),
        ];

        if ($paymentMethod == 'paygent') {
            $option[ self::SHIPPING_PAYMENT_STATUS_AUTHORIZED ] = __('Authorized');
            $option[ self::SHIPPING_PAYMENT_STATUS_SUSPECTED_FRAUD ] = __('Suspected fraud');
            $option[ self::SHIPPING_PAYMENT_STATUS_CAPTURE_FAILED ] = __('Capture failed');
        }

        return $option;
    }

    /**
     * @return array
     */
    public static function getAllOption()
    {
        $options = self::getOptionArray();
        array_unshift($options, ['value' => '', 'label' => '']);
        return $options;
    }

    /**
     * @return array
     */
    public static function getAllOptions()
    {
        $res = self::getOptions();
        array_unshift($res, ['value' => '', 'label' => '']);
        return $res;
    }

    /**
     * @return array
     */
    public static function getOptions()
    {
        $res = [];
        foreach (self::getOptionArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
        }
        return $res;
    }

    /**
     * @return array
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

    /**
     * @return array
     */
    public function toArray(){
        $res = [];
        foreach (self::getOptionArray() as $index => $value) {
            $res[$index] = $value;
        }
        return $res;
    }
}
