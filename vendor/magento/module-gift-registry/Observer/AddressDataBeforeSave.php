<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddressDataBeforeSave implements ObserverInterface
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
     * Check if gift registry prefix is set for customer address id
     * and set giftRegistryItemId
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $object = $observer->getEvent()->getDataObject();
        $addressId = $object->getCustomerAddressId();
        $prefix = $this->_giftRegistryData->getAddressIdPrefix();

        if (!is_numeric($addressId) && preg_match('/^' . $prefix . '([0-9]+)$/', $addressId)) {
            $object->setGiftregistryItemId(str_replace($prefix, '', $addressId));
        }
        return $this;
    }
}
