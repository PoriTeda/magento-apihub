<?php

namespace Riki\Customer\Model\ResourceModel\Address;

/**
 * Class represents save operations for customer address relations
 */
class Relation
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     */
    public function __construct(\Magento\Customer\Model\CustomerFactory $customerFactory)
    {
        $this->customerFactory = $customerFactory;
    }

    /**
     * Around process address related
     *
     * @param \Magento\Customer\Model\ResourceModel\Address\Relation $subject
     * @param $proceed
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    public function aroundProcessRelation(\Magento\Customer\Model\ResourceModel\Address\Relation $subject, $proceed,\Magento\Framework\Model\AbstractModel $object)
    {
        /**
         * @var $object \Magento\Customer\Model\Address
         */
        if (!$object->getIsCustomerSaveTransaction() && $this->isAddressDefault($object)) {
            $customer = $this->customerFactory->create()->load($object->getCustomerId());
            $changedAddresses = [];

            if ($object->getIsDefaultBilling() and $object->getId() != $customer->getData('default_billing')) {
                $changedAddresses['default_billing'] = $object->getId();
            }

            if ($object->getIsDefaultShipping() and $object->getId() != $customer->getData('default_shipping')) {
                $changedAddresses['default_shipping'] = $object->getId();
            }

            if ($changedAddresses) {
                $customer->getResource()->getConnection()->update(
                    $customer->getResource()->getTable('customer_entity'),
                    $changedAddresses,
                    $customer->getResource()->getConnection()->quoteInto('entity_id = ?', $customer->getId())
                );
            }
        }
    }

    /**
     * Checks if address has chosen as default and has had an id
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return bool
     */
    protected function isAddressDefault(\Magento\Framework\Model\AbstractModel $object)
    {
        return $object->getId() && ($object->getIsDefaultBilling() || $object->getIsDefaultShipping());
    }
}
