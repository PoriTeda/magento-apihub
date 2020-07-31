<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddressDataBeforeLoad implements ObserverInterface
{
    /**
     * Gift registry data
     *
     * @var \Magento\GiftRegistry\Helper\Data
     */
    protected $_giftRegistryData;

    /**
     * @param \Magento\GiftRegistry\Helper\Data $giftRegistryData
     */
    public function __construct(\Magento\GiftRegistry\Helper\Data $giftRegistryData)
    {
        $this->_giftRegistryData = $giftRegistryData;
    }

    /**
     * Customer address data object before load processing
     * Set gift registry item id flag
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $addressId = $observer->getEvent()->getValue();

        if (!is_numeric($addressId)) {
            $prefix = $this->_giftRegistryData->getAddressIdPrefix();
            $registryItemId = str_replace($prefix, '', $addressId);
            $object = $observer->getEvent()->getDataObject();
            $object->setGiftregistryItemId($registryItemId);
            $object->setCustomerAddressId($addressId);
        }
        return $this;
    }
}
