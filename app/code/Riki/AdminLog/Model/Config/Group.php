<?php
namespace  Riki\Customer\Model\Config ;

class Group implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Riki\FairAndSeasonalGift\Model\Fair
     */
    protected $_groupCollection;

    /**
     * Constructor
     *
     * @param \Magento\Customer\Model\ResourceModel\Group\Collection $groupCollection
     */
    public function __construct(\Magento\Customer\Model\ResourceModel\Group\Collection $groupCollection)
    {
        $this->_groupCollection = $groupCollection;
    }
    public function toOptionArray()
    {
        return $this->_groupCollection->toOptionArray();
    }
}