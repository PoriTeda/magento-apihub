<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog Product visibilite model and attribute source model
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Riki\Customer\Model\Address;

use Magento\Framework\Data\OptionSourceInterface;

class AddressType extends \Magento\Framework\DataObject implements OptionSourceInterface
{
    const HOME = 'home';           const HOME_TEXT = 'Home';

    const OFFICE = 'company';           const OFFICE_TEXT = 'Company';

    const SHIPPING = 'shipping';           const SHIPPING_TEXT = 'Shipping';


    /**
     * Retrieve option array
     *
     * @return array
     */
    public static function getOptionArray()
    {
        return [
            self::HOME => __(self::HOME_TEXT),
            self::OFFICE => __(self::OFFICE_TEXT),
            self::SHIPPING => __(self::SHIPPING_TEXT),
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
        $res[] = ['value' => '', 'label' => __('Please select Address Type')];
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
     * Set attribute instance
     *
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @return $this
     */

    public function toOptionArray()
    {
        return $this->getAllOptions();
    }

    /**
     * @param $searchLabel
     * @return bool|int|string
     */
    public function getIdByLabel($searchLabel){
        foreach (self::getOptionArray() as $key => $label){
            if($label == $searchLabel){
                return $key;
            }
        }
        return false;
    }
}
