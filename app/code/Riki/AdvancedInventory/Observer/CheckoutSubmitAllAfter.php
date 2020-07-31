<?php

namespace Riki\AdvancedInventory\Observer;

class CheckoutSubmitAllAfter extends \Wyomind\AdvancedInventory\Observer\CheckoutSubmitAllAfter
{
    /**
     * @var \Riki\Sales\Model\OrderCutoffDate
     */
    protected $_cutoffDate;

    /**
     * CheckoutSubmitAllAfter constructor.
     * @param AssignRegister $assignRegisterObserver
     * @param \Riki\AdvancedInventory\Model\Assignation $modelAssignation
     * @param \Wyomind\Core\Helper\Data $coreHelperData
     * @param \Magento\Payment\Helper\Data $paymentHelperData
     * @param \Magento\Sales\Helper\Data $salesHelperData
     * @param \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory
     * @param \Magento\Sales\Model\Order\Email\Container\OrderIdentity $identityContainer
     * @param \Magento\Sales\Model\Order\Email\Container\Template $templateContainer
     * @param \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Sales\Model\OrderCutoffDate $cutoffDate
     */
    public function __construct(
        \Riki\AdvancedInventory\Observer\AssignRegister $assignRegisterObserver,
        \Riki\AdvancedInventory\Model\Assignation $modelAssignation,
        \Wyomind\Core\Helper\Data $coreHelperData,
        \Magento\Payment\Helper\Data $paymentHelperData,
        \Magento\Sales\Helper\Data $salesHelperData,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        \Magento\Sales\Model\Order\Email\Container\OrderIdentity $identityContainer,
        \Magento\Sales\Model\Order\Email\Container\Template $templateContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Sales\Model\OrderCutoffDate $cutoffDate
    ) {
        parent::__construct(
            $modelAssignation,
            $coreHelperData,
            $paymentHelperData,
            $salesHelperData,
            $pointOfSaleFactory,
            $identityContainer,
            $templateContainer,
            $senderBuilderFactory,
            $logger
        );

        $this->_modelAssignation = $modelAssignation;
        $this->_cutoffDate = $cutoffDate;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if (!$order instanceof \Magento\Sales\Model\Order || !$order->getId()) {
            return;
        }

        $assignation = [];

        if (!empty($order->getData('assignation'))) {
            $inventory = $this->buildAssignationData($order->getData('assignation'));
            if (!empty($inventory)) {
                $assignation['inventory'] = $inventory;
            }
        }

        // Trace log NED-708
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/NED-708.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);

        $contentLog = "Start Trace Log NED-708 \r\n";
        $contentLog .= "Order ID #" . $order->getId() . "\r\n";

        if (isset($_SERVER['REQUEST_URI'])) {
            $contentLog .= 'URL: ' . $_SERVER['REQUEST_URI'];
        }

        if (!empty($assignation)) {
            $contentLog .= "Order assignation data : " . $order->getData('assignation') . "\r\n";
            try {
                $this->_modelAssignation->insertWithUpdateWhStock($order->getId(), $assignation, false);
            } catch (\Exception $e) {
                $this->_logger->critical($e->getMessage());
                $contentLog .= "Has error when insert with update Wh-Stock : " . $e->getMessage() . "\r\n";
            }

            $storeId = $order->getStore()->getId();

            if (isset($assignation['inventory']['place_ids'])) {
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
                    $emails = $this->getDataEmailNotification($posId);
                    $canSendMail = (!empty($emails) && isset($emails[0]) && $emails[0] != '') ? true : false;
                    if ($canSendMail && $order->getState() != \Magento\Sales\Model\Order::STATE_CANCELED) {
                        try {
                            foreach ($emails as $email) {
                                if ($this->validateEmailFormat($email)) {
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
                            $this->_logger->critical($e);
                        }
                    }
                }
            }
        }

        $contentLog .= "Calculate cut off date \r\n";
        //calculate cut off date for order item
        if (!$order->getMinExportDate()) {
            try {
                $this->_cutoffDate->calculateCutoffDate($order);
            } catch (\Exception $e) {
                $this->_logger->critical($e);
                $contentLog .= "Has error when calculate cut off date : " . $e->getMessage() . "\r\n";
            }
        }
        $contentLog .= $this->_cutoffDate->getContentLog();
        $contentLog .= "Stop Trace Log NED-708 \r\n";
        $logger->info($contentLog);
    }

    /**
     * Build assignation data based on json data
     *
     * @param $assignation
     * @return bool|mixed
     */
    public function buildAssignationData($assignation)
    {
        try {
            $inventory = \Zend_Json::decode($assignation);
            return $inventory;
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        return false;
    }

    /**
     * Validates the format of the email address
     *
     * @param $email
     * @return bool
     * @throws \Zend_Validate_Exception
     */
    private function validateEmailFormat($email)
    {
        if (!\Zend_Validate::is($email, 'EmailAddress')) {
            return false;
        }
        return true;
    }

    /**
     * Get data email notify config
     *
     * @param $posId
     * @return array|null
     */
    public function getDataEmailNotification($posId)
    {
        $dataEmail = $this->_pointOfSaleFactory->create()->load($posId)->getInventoryNotification();
        $emails = explode(',', $dataEmail);
        if (is_array($emails) && !empty($emails)) {
            return $emails;
        }
        return null;
    }
}
