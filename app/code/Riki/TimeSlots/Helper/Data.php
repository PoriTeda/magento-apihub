<?php
namespace Riki\TimeSlots\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_timeSlotCollectionFactory;

    protected $_timeSlotItems;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Riki\TimeSlots\Model\ResourceModel\TimeSlots\CollectionFactory $timeSlotCollectionFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);

        $this->_timeSlotCollectionFactory = $timeSlotCollectionFactory;
    }

    /**
     * @param $id
     * @return \Magento\Framework\DataObject|null
     */
    public function _getTimeSlotFromCollectionById($id){
        if(is_null($this->_timeSlotItems)){
            $this->_timeSlotItems = $this->_timeSlotCollectionFactory->create()->getItems();
        }

        return isset($this->_timeSlotItems[$id])? $this->_timeSlotItems[$id] : null;
    }

}