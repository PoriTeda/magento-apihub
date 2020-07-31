<?php

namespace Riki\Customer\Model\ResourceModel\Customer;

use Magento\Customer\Api\AddressMetadataInterface;

/**
 * Class Relation
 */
class Relation
{
    /**
     * Around process customer related
     *
     * @param \Magento\Customer\Model\ResourceModel\Customer\Relation $subject
     * @param $proceed
     * @param \Magento\Framework\Model\AbstractModel $customer
     */
    public function aroundProcessRelation(\Magento\Customer\Model\ResourceModel\Customer\Relation $subject, $proceed,\Magento\Framework\Model\AbstractModel $customer)
    {
        $defaultBillingId = $customer->getData('default_billing');
        $defaultShippingId = $customer->getData('default_shipping');
        $changedAddresses = [];
        /** @var \Magento\Customer\Model\Address $address */
        foreach ($customer->getAddresses() as $address) {
            if ($address->getData('_deleted')) {
                if ($address->getId() == $defaultBillingId) {
                    $customer->setData('default_billing', null);
                }

                if ($address->getId() == $defaultShippingId) {
                    $customer->setData('default_shipping', null);
                }

                $removedAddressId = $address->getId();
                $address->delete();

                // Remove deleted address from customer address collection
                $customer->getAddressesCollection()->removeItemByKey($removedAddressId);
            } else {
                // Need to use attribute set id to prevent loss data
                if (!$address->getAttributeSetId()) {
                    $address->setAttributeSetId(AddressMetadataInterface::ATTRIBUTE_SET_ID_ADDRESS);
                }
                $address->setParentId(
                    $customer->getId()
                )->setStoreId(
                    $customer->getStoreId()
                )->setIsCustomerSaveTransaction(
                    true
                )->save();

                if (($address->getIsPrimaryBilling() ||
                        $address->getIsDefaultBilling()) && $address->getId() != $defaultBillingId
                ) {
                    $customer->setData('default_billing', $address->getId());
                    $changedAddresses['default_billing'] = $address->getId();
                }

                if (($address->getIsPrimaryShipping() ||
                        $address->getIsDefaultShipping()) && $address->getId() != $defaultShippingId
                ) {
                    $customer->setData('default_shipping', $address->getId());
                    $changedAddresses['default_shipping'] = $address->getId();
                }
            }
        }


        if ($changedAddresses) {
            $changedAddresses['default_billing'] = $customer->getData('default_billing');
            $changedAddresses['default_shipping'] = $customer->getData('default_shipping');

            $customer->getResource()->getConnection()->update(
                $customer->getResource()->getTable('customer_entity'),
                $changedAddresses,
                $customer->getResource()->getConnection()->quoteInto('entity_id = ?', $customer->getId())
            );
        }
    }
}
