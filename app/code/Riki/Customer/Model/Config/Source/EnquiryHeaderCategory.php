<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IsActive
 */
class EnquiryHeaderCategory implements OptionSourceInterface
{
    /**
     * @var \Riki\Customer\Model\ResourceModel\CategoryEnquiry\CollectionFactory
     */
    protected $collectionFactory;

    public function __construct(
        \Riki\Customer\Model\ResourceModel\CategoryEnquiry\CollectionFactory $collectionFactory
    )
    {
        $this->collectionFactory = $collectionFactory;
    }
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $aEnquireHeaderCategories = $this->collectionFactory->create()->getData();
        foreach($aEnquireHeaderCategories as $aEnquireHeaderCategory){
            $options[] = ['label'=>$aEnquireHeaderCategory['code'].' - '.$aEnquireHeaderCategory['name'], 'value'=>$aEnquireHeaderCategory['entity_id']];
        }
        return $options;
    }
    /**
     * Retrieve option array
     *
     * @return string[]
     */
    public function getOptionArray()
    {
        $options = [];
        $aEnquireHeaderCategories = $this->collectionFactory->create()->getData();
        foreach($aEnquireHeaderCategories as $aEnquireHeaderCategory){
            $options[$aEnquireHeaderCategory['entity_id']] = $aEnquireHeaderCategory['code'].' - '.$aEnquireHeaderCategory['name'];
        }
        return $options;
    }

    /**
     * Retrieve option array with empty value
     *
     * @return string[]
     */
    public function getAllOptions()
    {
        $result = [];

        foreach (self::getOptionArray() as $index => $value) {
            $result[] = ['value' => $index, 'label' => $value];
        }

        return $result;
    }

    /**
     * Retrieve option text by option value
     *
     * @param string $optionId
     * @return string
     */
    public function getOptionText($optionId)
    {
        $options = self::getOptionArray();

        return isset($options[$optionId]) ? $options[$optionId] : null;
    }
}
