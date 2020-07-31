<?php

namespace Riki\AdvancedInventory\Observer;

class AssignationProcess implements \Magento\Framework\Event\ObserverInterface
{
    const EVENT_AFTER_ASSIGNATION_SUCCESS = 'after_assignation_success';
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Wyomind\Core\Helper\Data
     */
    protected $coreHelperData;
    /**
     * @var \Riki\AdvancedInventory\Model\Assignation
     */
    protected $modelAssignation;
    /**
     * @var \Riki\AdvancedInventory\Observer\AssignRegister
     */
    protected $assignRegisterObserver;

    /** @var \Riki\AdvancedInventory\Model\ResourceModel\Order  */
    protected $aiOrderResourceFactory;

    /**
     * AssignationProcess constructor.
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Wyomind\Core\Helper\Data $coreHelperData
     * @param \Riki\AdvancedInventory\Model\Assignation $modelAssignation
     * @param AssignRegister $assignRegisterObserver
     * @param \Riki\AdvancedInventory\Model\ResourceModel\OrderFactory $aiOrderResourceFactory
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Psr\Log\LoggerInterface $logger,
        \Wyomind\Core\Helper\Data $coreHelperData,
        \Riki\AdvancedInventory\Model\Assignation $modelAssignation,
        \Riki\AdvancedInventory\Observer\AssignRegister $assignRegisterObserver,
        \Riki\AdvancedInventory\Model\ResourceModel\OrderFactory $aiOrderResourceFactory
    ) {
        $this->eventManager = $eventManager;
        $this->logger = $logger;
        $this->modelAssignation = $modelAssignation;
        $this->coreHelperData = $coreHelperData;
        $this->assignRegisterObserver = $assignRegisterObserver;
        $this->aiOrderResourceFactory = $aiOrderResourceFactory;
    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->coreHelperData->getStoreConfig("advancedinventory/settings/enabled")
            || !$this->coreHelperData->getDefaultConfig("advancedinventory/settings/autoassign_order")
        ) {
            $this->logger->info('Assignation: Advanced Inventory config was disabled');
            return;
        }

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getObject();

        /*reject simulate process*/
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }

        if (!$order instanceof \Magento\Sales\Model\Order || !$order->getId()) {
            return;
        }

        if (!$this->assignRegisterObserver->getWaitingByQuoteId($order->getQuoteId())) {
            return;
        }

        $this->assignRegisterObserver->setProcessingByQuoteId($order->getQuoteId());

        $this->modelAssignation->order = $order;

        $assignation = $this->modelAssignation->generateAssignationByOrder($order, true);

        //set assignation for order
        $orderAssignationData = \Zend_Json::encode($assignation['inventory']);

        $order->setAssignation($orderAssignationData);
        $order->setAssignedTo($assignation['inventory']['place_ids']);

        $this->aiOrderResourceFactory->create()->saveAssignation($order->getId(), $assignation['inventory']);

        $this->assignRegisterObserver->setAssignedByQuoteId($order->getQuoteId());

        $this->eventManager->dispatch(
            self::EVENT_AFTER_ASSIGNATION_SUCCESS, ['order' => $order])
        ;
    }
}
