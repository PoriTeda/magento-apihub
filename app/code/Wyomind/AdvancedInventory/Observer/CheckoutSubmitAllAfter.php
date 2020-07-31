<?php

/**
 * Copyright Â© 2015 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Observer;

class CheckoutSubmitAllAfter implements \Magento\Framework\Event\ObserverInterface
{

    protected $_modelAssignation;
    protected $_coreHelperData;
    protected $_salesHelperData;
    protected $_pointOfSaleFactory;
    protected $_paymentHelperData;
    protected $_identityContainer;
    protected $_templateContainer;
    protected $_senderBuilderFactory;
    protected $_logger;

    public function __construct(
        \Wyomind\AdvancedInventory\Model\Assignation $modelAssignation,
        \Wyomind\Core\Helper\Data $coreHelperData,
        \Magento\Payment\Helper\Data $paymentHelperData,
        \Magento\Sales\Helper\Data $salesHelperData,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        \Magento\Sales\Model\Order\Email\Container\OrderIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\Container\Template $templateContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger
    ) {

        $this->_modelAssignation = $modelAssignation;
        $this->_coreHelperData = $coreHelperData;
        $this->_salesHelperData = $salesHelperData;
        $this->_paymentHelperData = $paymentHelperData;
        $this->_pointOfSaleFactory = $pointOfSaleFactory;
        $this->_identityContainer = $identityContainer;
        $this->_templateContainer = $templateContainer;
        $this->_senderBuilderFactory = $senderBuilderFactory;
        $this->_logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        if ($this->_coreHelperData->getStoreConfig("advancedinventory/settings/enabled") && $this->_coreHelperData->getDefaultConfig("advancedinventory/settings/autoassign_order")) {
            $this->_modelAssignation->order = $observer->getEvent()->getOrder();
            $order = $observer->getEvent()->getOrder();
            $entityId = $observer->getEvent()->getOrder()->getEntityId();
            $assignation = $this->_modelAssignation->run($entityId);
            $this->_modelAssignation->insert($entityId, $assignation);


            $storeId = $order->getStore()->getId();

            if (!$this->_salesHelperData->canSendNewOrderEmail($storeId)) {
                return;
            }
            if (!isset($assignation['inventory']['place_ids'])) {
                return;
            }



            $posIds = explode(",", $assignation['inventory']['place_ids']);
            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId
            ];
            $this->_templateContainer->setTemplateOptions($templateOptions);

            if ($order->getCustomerIsGuest()) {
                $templateId = $this->_identityContainer->getGuestTemplateId();
                $customerName = $order->getBillingAddress()->getName();
            } else {
                $templateId = $this->_identityContainer->getTemplateId();
                $customerName = $order->getCustomerName();
            }

            $this->_identityContainer->setCustomerName($customerName);

            $this->_templateContainer->setTemplateId($templateId);

            foreach ($posIds as $posId) {
                // Get the destination email addresses to send copies to
                $emails = explode(',', $this->_pointOfSaleFactory->create()->load($posId)->getInventoryNotification());
                $countEmails = count($emails);
                if ($countEmails > 0 && $order->getState() != \Magento\Sales\Model\Order::STATE_CANCELED && $emails[0] != '') {
                    try {
                        if ($countEmails) {
                            foreach ($emails as $email) {
                                $this->_identityContainer->setCustomerEmail($email);
                                $sender = $this->_senderBuilderFactory->create(
                                    [
                                            'templateContainer' => $this->_templateContainer,
                                            'identityContainer' => $this->_identityContainer,
                                        ]
                                );
                                $sender->send();
                            }
                        }
                    } catch (\Exception $e) {
                        $this->_logger->error($e->getMessage());
                    }
                }
            }
        }
    }
}
