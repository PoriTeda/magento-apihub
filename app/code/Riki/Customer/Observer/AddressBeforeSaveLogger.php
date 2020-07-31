<?php
namespace Riki\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddressBeforeSaveLogger implements ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Customer\Model\Address $address */
        $address = $observer->getEvent()->getCustomerAddress();

        if ($address->isObjectNew()) {
            $address->setData('is_created_new', true);
        }
    }
}