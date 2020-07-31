<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * AMB Customer region attribute source
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Riki\Customer\Model\ResourceModel\AmbAddress;

class Region extends \Magento\Eav\Model\Entity\Attribute\Source\Table
{
    const COUNTRY_JP = 'JP';
    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory
     */
    protected $_regionsFactory;

    /**
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory $attrOptionFactory
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionsFactory
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory $attrOptionFactory,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionsFactory
    ) {
        $this->_regionsFactory = $regionsFactory;
        parent::__construct($attrOptionCollectionFactory, $attrOptionFactory);
    }

    /**
     * Retrieve all region options
     *
     * @return array
     */
    public function getAllOptions($withEmpty = true, $defaultValues = false)
    {
        if (!$this->_options) {
            $this->_options = $this->_createRegionsCollection()->load()->toOptionArray();
        }
        foreach ($this->_options as $key => $item){
            if(isset($item['country_id']) && $item['country_id'] != self::COUNTRY_JP){
                unset($this->_options[$key]);
            }
        }
        return $this->_options;
    }

    /**
     * @return \Magento\Directory\Model\ResourceModel\Region\Collection
     */
    protected function _createRegionsCollection()
    {
        return $this->_regionsFactory->create();
    }
}