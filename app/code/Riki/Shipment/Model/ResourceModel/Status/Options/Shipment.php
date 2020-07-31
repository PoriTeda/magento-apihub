<?php
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
class  Shipment implements OptionSourceInterface
{
    const SHIPMENT_STATUS_CREATED = 'created';
    const SHIPMENT_STATUS_EXPORTED = 'exported';
    const SHIPMENT_STATUS_EXPORTED_PARTIAL = 'exported_partial';
    const SHIPMENT_STATUS_SHIPPED_OUT = 'shipped_out';
    const SHIPMENT_STATUS_SHIPPED_OUT_PARTIAL = 'shipped_out_partial';
    const SHIPMENT_STATUS_DELIVERY_COMPLETED = 'delivery_completed';
    const SHIPMENT_STATUS_DELIVERY_COMPLETED_PARTIAL = 'delivery_completed_partial';
    const SHIPMENT_STATUS_REJECTED = 'rejected';
    const SHIPMENT_STATUS_CANCEL = 'canceled';

    /**
     * @return array
     */
    public static function getOptionArray()
    {
        return [
            self::SHIPMENT_STATUS_CREATED => __('Created'),
            self::SHIPMENT_STATUS_EXPORTED => __('Exported to WMS'),
            self::SHIPMENT_STATUS_REJECTED => __('Rejected'),
            self::SHIPMENT_STATUS_SHIPPED_OUT => __('Shipped Out'),
            self::SHIPMENT_STATUS_DELIVERY_COMPLETED => __('Delivery Completed'),
            self::SHIPMENT_STATUS_CANCEL => __('Canceled')
        ];
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
}
