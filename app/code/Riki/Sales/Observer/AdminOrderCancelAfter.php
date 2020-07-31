<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;

class AdminOrderCancelAfter implements ObserverInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $_profileFactory;
    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $_dataHelper;

    /**
     * @var \Riki\Fraud\Helper\CedynaThreshold
     */
    protected $_cedynaThreshold;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfileData;

    /**
     * AdminOrderCancelAfter constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\Sales\Helper\Data $dataHelperSales
     * @param \Riki\Fraud\Helper\CedynaThreshold $cedynaThreshold
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfileData
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Sales\Helper\Data $dataHelperSales,
        \Riki\Fraud\Helper\CedynaThreshold $cedynaThreshold,
        \Riki\Subscription\Helper\Profile\Data $helperProfileData
    ) {
        $this->_logger = $logger;
        $this->_profileFactory = $profileFactory;
        $this->_dataHelper = $dataHelperSales;
        $this->_cedynaThreshold = $cedynaThreshold;
        $this->helperProfileData = $helperProfileData;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @throws \Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getOrder();

        /* revert shopping point after cancel order */
        if ($order->getUsedPoint() > 0) {
            $this->revertShoppingPoint($order);
        }

        if (($order->getData('riki_type') == \Riki\Sales\Model\OrderCutoffDate::ORDER_SUBSCRIPTION ||
            $order->getData('riki_type') == \Riki\Sales\Helper\Order::RIKI_TYPE_DELAY_PAYMENT )
            && $order->getData('subscription_profile_id') != null
            && !$order->getData(\Riki\Subscription\Helper\Order\Data::IS_INCOMPLETE_GENERATE_PROFILE_ORDER)
        ) {
            try {
                $this->updateProfileInfo($order->getData('subscription_profile_id'), $order);
            } catch (\Exception $exception) {
                $this->_logger->critical($exception);
            }
        }

        /* revert cedyna monthly counter for business after cancel order */
        $this->revertCedynaCounter($order->getId());



        /* cancel prize after cancel order */
        $this->cancelPrize($order);

        /* cancel shipment after cancel order */
        $this->cancelShipment($order);
        if ($order->getShipmentsCollection()->getSize() > 0) {
            $order->setShipmentStatus(ShipmentStatus::SHIPMENT_STATUS_CANCEL);

            try {
                $order->save();
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }

        return $this;
    }

    /**
     * revert cedyna monthly counter for business after cancel order
     *
     * @param $orderId
     */
    public function revertCedynaCounter($orderId)
    {
        try {
            $this->_cedynaThreshold->updateCedynaValueAfterCancelOrder($orderId);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * revert shopping point after cancel order
     *
     * @param $order
     */
    public function revertShoppingPoint($order)
    {
        try {
            $this->_dataHelper->revertShoppingPointCancel($order);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * cancel prize after cancel order
     *
     * @param $order
     */
    public function cancelPrize($order)
    {
        try {
            $this->_dataHelper->cancelPrize($order);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * cancel shipment after cancel order
     *
     * @param $order
     */
    public function cancelShipment($order)
    {
        try {
            $this->_dataHelper->cancelShipment($order);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * update Profile sales_count and sales_value_count
     *
     * @param $subscriptionProfileId
     * @param \Magento\Sales\Model\Order $oldOrder
     * @throws \Exception
     */
    private function updateProfileInfo($subscriptionProfileId, \Magento\Sales\Model\Order $oldOrder) {
        $profileModel = $this->_profileFactory->create()->load($subscriptionProfileId, null, true);
        if ($profileModel->getId()) {
            $salesCount = $profileModel->getData('sales_count');
            $salesValueCount = $profileModel->getData('sales_value_count');
            $oldSalesCount = 0;

            foreach ($oldOrder->getAllItems() as $oldItem) {
                if ($oldItem->getProductType() ==  \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                    continue;
                }
                $buyRequest = $oldItem->getBuyRequest();

                if (isset($buyRequest['options']['ampromo_rule_id'])) {
                    continue;
                }
                if ($oldItem->getData('prize_id')) {
                    continue;
                }
                if ($oldItem->getData('is_riki_machine') and $oldItem->getData('price') == 0) {
                    continue;
                }
                $oldSalesCount += $oldItem->getQtyOrdered();
            }
            $oldSalesValueCount = $oldOrder->getGrandTotal();

            $salesCount = $salesCount - $oldSalesCount;
            $salesValueCount = $salesValueCount - $oldSalesValueCount;

            $profileModel->setData('sales_count', $salesCount);
            $profileModel->setData('sales_value_count', $salesValueCount);
            try {
                $profileModel->save();
            } catch (\Exception $e) {
                throw $e;
            }
            if ($versionId = $this->helperProfileData->checkProfileHaveVersion($subscriptionProfileId)) {
                $versionProfileModel = $this->_profileFactory->create()->load($versionId);
                if ($versionProfileModel->getId()) {
                    $versionProfileModel->setData('sales_count', $salesCount);
                    $versionProfileModel->setData('sales_value_count', $salesValueCount);
                    try {
                        $versionProfileModel->save();
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
            }
            if ($tmp = $this->helperProfileData->getTmpProfile($subscriptionProfileId)) {
                $tmpId = $tmp->getData('linked_profile_id');
                $tmpProfileModel = $this->_profileFactory->create()->load($tmpId);
                if ($tmpProfileModel->getId()) {
                    $tmpProfileModel->setData('sales_count', $salesCount);
                    $tmpProfileModel->setData('sales_value_count', $salesValueCount);
                    try {
                        $tmpProfileModel->save();
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
            }
        }
    }
}
