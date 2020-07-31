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
namespace Riki\ShipmentExporter\Model\Config;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Eav\Model\Config;

class Phcode extends \Magento\Framework\DataObject implements OptionSourceInterface
{

    /**
     * Reference to the attribute instance
     *
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    protected $_attribute;

    /**
     * Eav entity attribute
     *
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $_eavEntityAttribute;

    /**
     * @var
     */
    protected $_eavConfig;


    /**
     * Phcode constructor.
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavEntityAttribute
     * @param array $data
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavEntityAttribute,
        \Magento\Eav\Model\Config $eavConfig,
        array $data = []

    ) {
        $this->_eavEntityAttribute = $eavEntityAttribute;
        $this->_eavConfig = $eavConfig;
        parent::__construct($data);
    }


    /**
     * Retrieve option array
     *
     * @return array
     */
    public static function getOptionArray()
    {
        return [
            self::MATERIAL_FERT => __('FERT'),
            self::MATERIAL_HALB => __('HALB (Z100)'),
            self::MATERIAL_ZSIM => __('ZSIM'),
            self::MATERIAL_UNBW => __('UNBW'),
            self::MATERIAL_DIEN => __('DIEN')
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
    public function getAllOptions()
    {
//        $res = [];
//        foreach (self::getOptionArray() as $index => $value) {
//            $res[] = ['value' => $index, 'label' => $value];
//        }
//        return $res;
        $attribute = $this->_eavConfig->getAttribute('catalog_product', 'ph_code');
        $options = $attribute->getSource()->getAllOptions();
        return $options;
    }

    /**
     * Retrieve option text
     *
     * @param int $optionId
     * @return string
     */
//    public static function getOptionText($optionId)
//    {
//        $options = self::getOptionArray();
//        return isset($options[$optionId]) ? $options[$optionId] : null;
//    }


    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $attribute = $this->_eavConfig->getAttribute('catalog_product', 'ph_code');
        $options = $attribute->getSource()->getAllOptions();
        return $options;
    }
}
