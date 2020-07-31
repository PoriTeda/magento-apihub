<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesOrderAddressAfterSave implements ObserverInterface
{
    /**
     * @var \Magento\CustomerCustomAttributes\Model\Sales\Order\AddressFactory
     */
    protected $orderAddressFactory;

    /**
     * @param \Magento\CustomerCustomAttributes\Model\Sales\Order\AddressFactory $orderAddressFactory
     */
    public function __construct(
        \Magento\CustomerCustomAttributes\Model\Sales\Order\AddressFactory $orderAddressFactory
    ) {
        $this->orderAddressFactory = $orderAddressFactory;
    }

    /**
     * After save observer for order address
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $orderAddress = $observer->getEvent()->getAddress();
        if ($orderAddress instanceof \Magento\Framework\Model\AbstractModel) {
            /** @var $orderAddressModel \Magento\CustomerCustomAttributes\Model\Sales\Order\Address */
            $orderAddressModel = $this->orderAddressFactory->create();
            $orderAddressModel->saveAttributeData($orderAddress);
        }
        return $this;
    }
}
