<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category  Riki_Shipment
 * @package   Riki\Shipment\Model\ResourceModel
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Shipment\Model\ResourceModel\Status\Options;
use Magento\Framework\Data\OptionSourceInterface;
/**
 * Class Hours
 *
 * @category  Riki_Shipment
 * @package   Riki\Shipment\Model\ResourceModel
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class DeliveryType implements OptionSourceInterface
{
    /**
     * @return array
     */
    public static function getOptionArray()
    {
        return [
            'chilled' => __('Chilled'),
            'cold' => __('Cold'),
            'cool' => __('Cool'),
            'cool_normal_directmail' => __('Cool').','.__('Normal').','.__('DM'),
            'cosmetic' => __('Cosmetic'),
            'direct_mail' => __('DM'),
            'normal' => __('Normal'),
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
