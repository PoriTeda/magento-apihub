<?php

namespace Bluecom\PaymentCustomer\Model\Source\Config\Customer;

class Group implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var array
     */
    protected $_customerGroups;
    /**
     * @var \Magento\Customer\Api\GroupManagementInterface
     */
    protected $_groupManagement;

    /**
     * @var \Magento\Framework\Convert\DataObject
     */
    protected $_converter;

    /**
     * Group constructor.
     *
     * @param \Magento\Customer\Api\GroupManagementInterface $groupManagementInterface GroupManagementInterface
     * @param \Magento\Framework\Convert\DataObject          $converter                DataObject
     */
    public function __construct(
        \Magento\Customer\Api\GroupManagementInterface $groupManagementInterface,
        \Magento\Framework\Convert\DataObject $converter
    ) {
        $this->_groupManagement = $groupManagementInterface;
        $this->_converter = $converter;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        return $this->_getCustomerGroups();
    }

    /**
     * Retrieve customer groups
     *
     * @return array
     */
    protected function _getCustomerGroups()
    {
        $notLoggedInGroup = $this->_groupManagement->getNotLoggedInGroup();
        $groups = $this->_groupManagement->getLoggedInGroups();
        if ($this->_customerGroups === null) {
            $this->_customerGroups = [];
            $this->_customerGroups[] = [
                'value' => $notLoggedInGroup->getId(),
                'label' => $notLoggedInGroup->getCode()
            ];
            foreach ($groups as $group) {
                $this->_customerGroups[] = [
                    'value' => $group->getId(),
                    'label' => $group->getCode()
                ];
            }
            array_unshift($this->_customerGroups, ['value' => '', 'label' => __('-- Please Select ---')]);
        }


        return $this->_customerGroups;

    }

}