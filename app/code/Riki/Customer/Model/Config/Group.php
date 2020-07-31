<?php
namespace  Riki\Customer\Model\Config ;

class Group implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\Collection
     */
    protected $_collection;
    /**
     * Group constructor.
     * @param \Magento\Customer\Model\ResourceModel\Group\Collection $collection
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Group\Collection $collection
    )
    {
        $this->_collection = $collection;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_collection->toOptionArray();
    }
}