<?php
namespace Riki\ArReconciliation\Model\ResourceModel\Status;

use Magento\Framework\Data\OptionSourceInterface;

class ShipmentStatus extends \Magento\Framework\DataObject implements OptionSourceInterface
{
    const SHIPMENT_CREATED = 'created';
    const SHIPMENT_EXPORTED = 'exported';
    const SHIPMENT_REJECTED = 'rejected';
    const SHIPMENT_SHIPPED_OUT = 'shipped_out';
    const SHIPMENT_DELIVERY_COMPLETED = 'delivery_completed';

    /**
     * ShipmentStatus constructor.
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
            self::SHIPMENT_CREATED => __('created'),
            self::SHIPMENT_EXPORTED => __('exported'),
            self::SHIPMENT_REJECTED => __('Rejected'),
            self::SHIPMENT_SHIPPED_OUT => __('shipped out'),
            self::SHIPMENT_DELIVERY_COMPLETED => __('delivery completed')
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
