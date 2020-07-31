<?php

namespace Riki\SubscriptionProfileDisengagement\Model\Config\Source;

use \Riki\SubscriptionProfileDisengagement\Model\Reason as DisengagementReason;

class Reason implements \Magento\Framework\Option\ArrayInterface
{

    protected $_collection;

    protected $_options;

    protected $_codeToTitleOptions;

    public function __construct(
        \Riki\SubscriptionProfileDisengagement\Model\ResourceModel\Reason\CollectionFactory $collectionFactory
    ){
        $this->_collection = $collectionFactory;
    }

    /**
     * @return array
     */
    protected function _getOptionList(){

        if(is_null($this->_options)){

            $this->_options = [];

            $collection = $this->_collection->create()->addActiveFilter();
            $collection->addFieldToFilter(
                'visibility',
                ['in' => [DisengagementReason::VISIBILITY_BACKEND, DisengagementReason::VISIBILITY_BOTH]]
            );
            $collection->addFieldToFilter('status', DisengagementReason::STATUS_ACTIVE);
            foreach($collection as $reason){
                $this->_options[$reason->getId()] = $reason->getCode() . '-' .$reason->getTitle();
            }
        }

        return $this->_options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_getOptionList();
    }

    /**
     * @return array
     */
    public function codeToTitle(){
        if(is_null($this->_codeToTitleOptions)){

            $this->_codeToTitleOptions = [];

            $collection = $this->_collection->create()->addActiveFilter();

            foreach($collection as $reason){
                $this->_codeToTitleOptions[$reason->getCode()] = $reason->getCode() . '-' .$reason->getTitle();
            }
        }

        return $this->_codeToTitleOptions;
    }

    /**
     * Return option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->_getOptionList() as $key    =>  $value) {
            $options[] = ['label' => $value, 'value' => $key];
        }

        return $options;
    }

    /**
     * get Title by code
     *
     * @param $code
     * @return \Magento\Framework\Phrase
     */
    public function getTitleByCode($code){
        if(isset($this->_getOptionList()[$code]))
            return $this->_getOptionList()[$code];
        return __('Unknown');
    }
}
