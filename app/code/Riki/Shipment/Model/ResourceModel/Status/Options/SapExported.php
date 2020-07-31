<?php

namespace Riki\Shipment\Model\ResourceModel\Status\Options;

use Magento\Framework\Data\OptionSourceInterface;
use Riki\SapIntegration\Model\Api\Shipment as ShipmentApi;

class SapExported implements OptionSourceInterface
{
    /**
     * @return array
     */
    public static function getOptionArray()
    {
        return [
            ShipmentApi::NO_NEED_TO_EXPORT => __('No need to export to SAP'),
            ShipmentApi::WAITING_FOR_EXPORT => __('Waiting for export'),
            ShipmentApi::EXPORTED_TO_SAP => __('Exported to SAP'),
            ShipmentApi::FAILED_TO_EXPORT => __('Failed to export to SAP'),
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
