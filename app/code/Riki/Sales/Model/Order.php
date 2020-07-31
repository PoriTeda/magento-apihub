<?php

namespace Riki\Sales\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus;

class Order extends \Magento\Sales\Model\Order
{

    // Status cancel for shipment exported
    const PROCESSING_CANCELED = 'processing_canceled';
    const STOCK_POINT_DELIVERY_BUCKET_ID = 'stock_point_delivery_bucket_id';
    const STOCK_POINT_LABEL = '_STOCKPOINT';

    /**
     *
     */
    public function afterSave()
    {
        parent::afterSave();

        if ($this->getStatus() == \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_PENDING_CRD_REVIEW &&
            $this->getState() != self::STATE_HOLDED
        ) {
            $this->_logger->critical(new LocalizedException(
                __('The order %1 status has been set as incorrect data', $this->getIncrementId())
            ));
        }

        return $this;
    }

    /**
     * @param bool $ignoreSalable
     * @return bool
     */
    protected function _canReorder($ignoreSalable = false)
    {
        if ($this->getShipmentsCollection()->getSize()) {
            return false;
        }

        return parent::_canReorder($ignoreSalable);
    }

    /**
     * @return string
     */
    public function getCustomerName()
    {
        if ($this->getCustomerFirstname()) {
            $customerName = $this->getCustomerLastname() . ' ' . $this->getCustomerFirstname();
        } else {
            $customerName = (string)__('Guest');
        }
        return $customerName;
    }

    /**
     * Prepare order totals to cancellation
     *
     * @param string $comment
     * @param bool $graceful
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function registerCancellation($comment = '', $graceful = true)
    {
        if ($this->canCancel() || $this->isPaymentReview() || $this->isFraudDetected()) {
            $state = self::STATE_CANCELED;
            foreach ($this->getAllItems() as $item) {
                if ($state != self::STATE_PROCESSING && $item->getQtyToRefund()) {
                    if ($item->getQtyToShip() > $item->getQtyToCancel()) {
                        $state = self::STATE_PROCESSING;
                    } else {
                        $state = self::STATE_COMPLETE;
                    }
                }
                $item->cancel();
            }

            $this->setSubtotalCanceled($this->getSubtotal() - $this->getSubtotalInvoiced());
            $this->setBaseSubtotalCanceled($this->getBaseSubtotal() - $this->getBaseSubtotalInvoiced());

            $this->setTaxCanceled($this->getTaxAmount() - $this->getTaxInvoiced());
            $this->setBaseTaxCanceled($this->getBaseTaxAmount() - $this->getBaseTaxInvoiced());

            $this->setShippingCanceled($this->getShippingAmount() - $this->getShippingInvoiced());
            $this->setBaseShippingCanceled($this->getBaseShippingAmount() - $this->getBaseShippingInvoiced());

            $this->setDiscountCanceled(abs($this->getDiscountAmount()) - $this->getDiscountInvoiced());
            $this->setBaseDiscountCanceled(abs($this->getBaseDiscountAmount()) - $this->getBaseDiscountInvoiced());

            $this->setTotalCanceled($this->getGrandTotal() - $this->getTotalPaid());
            $this->setBaseTotalCanceled($this->getBaseGrandTotal() - $this->getBaseTotalPaid());
            if ($this->getPayment()->getMethod() == \Riki\CvsPayment\Model\CvsPayment::PAYMENT_METHOD_CVS_CODE) {
                if ($this->getData('payment_status') == PaymentStatus::PAYMENT_COLLECTED) {
                    $cancelCvsStatus = OrderStatus::STATUS_ORDER_CVS_CANCELLATION_WITH_PAYMENT ;
                } else {
                    $cancelCvsStatus = OrderStatus::STATUS_ORDER_HOLD_CVS_NOPAYMENT ;
                }
                $this->setState($state)
                    ->setStatus($cancelCvsStatus);
            } else {
                $exportShipment = $this->checkExportedShipment();
                if ($exportShipment) {
                    $this->setState($state)
                        ->setStatus(self::PROCESSING_CANCELED);
                } else {
                    $this->setState($state)
                        ->setStatus($this->getConfig()->getStateDefaultStatus($state));
                }
            }
            
            if (!empty($comment)) {
                $this->addStatusHistoryComment($comment, false);
            }
        } elseif (!$graceful) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We cannot cancel this order.'));
        }
        return $this;
    }

    /**
     * Check shipment exported
     *
     * @return bool
     */
    public function checkExportedShipment()
    {
        $shipExport = false;
        $allShipment = $this->getShipmentsCollection();
        if ($allShipment->getSize()) {
            foreach ($allShipment as $shipment) {
                if ($shipment->getData('shipment_status') != null
                    && $shipment->getData('shipment_status') == 'exported'
                ) {
                    $shipExport = true;
                }
            }
        }
        return $shipExport;
    }

    /**
     * customer shipping address
     *      additional data, only exist for stock point order
     *      (for a case this order use stock point address instead customer address)
     *
     * @return \Magento\Sales\Model\Order\Address|null
     */
    public function getCustomerShippingAddress()
    {
        foreach ($this->getAddresses() as $address) {
            if ($address->getAddressType() == \Riki\Quote\Model\Quote\Address::ADDRESS_TYPE_CUSTOMER
                && !$address->isDeleted()
            ) {
                return $address;
            }
        }
        return null;
    }

    /**
     * Override function getStatusLabel
     * @return string
     */
    public function getStatusLabel()
    {
        if ($this->getStatus() == OrderStatus::STATUS_ORDER_COMPLETE
        && $this->getData(self::STOCK_POINT_DELIVERY_BUCKET_ID)) {
            $stockpointLabel = strtoupper($this->getStatus().self::STOCK_POINT_LABEL);
            return __($stockpointLabel);
        }
        return $this->getConfig()->getStatusLabel($this->getStatus());
    }

    /**
     * {@inheritdoc}
     */
    public function getPayment()
    {
        $payment = $this->getData(OrderInterface::PAYMENT);
        if ($payment === null) {
            $paymentItems = $this->getPaymentsCollection()
                ->getItems();
            if (!empty($paymentItems)) {
                foreach ($paymentItems as $payment) {
                    $this->setData(
                        OrderInterface::PAYMENT,
                        $payment
                    );
                }
            }
        }
        if ($payment) {
            $payment->setOrder($this);
        }
        return $payment;
    }

    /**
     * @param null $key
     * @return $this
     */
    public function unsetData($key = null)
    {
        if ($key == 'min_export_date') {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/NED-708.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);

            $logData = [
                'msg' => 'updatedtonull-unsetData',
                'order_id' => $this->getId()
            ];

            $exception = new \Exception(json_encode($logData));
            $logger->info($exception->getMessage() . "\n" . $exception->getTraceAsString());
        }

        return parent::unsetData($key);
    }

    /**
     * @param mixed $key
     * @param null $value
     * @return $this
     */
    public function setData($key, $value = null)
    {
        if ($key == 'min_export_date' && !$value && $this->getOrigData('min_export_date')) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/NED-708.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);

            $logData = [
                'msg' => 'updatedtonull-setData',
                'order_id' => $this->getId()
            ];

            $exception = new \Exception(json_encode($logData));
            $logger->info($exception->getMessage() . "\n" . $exception->getTraceAsString());
        }

        return parent::setData($key, $value);
    }

    /**
     * Retrieve order shipment availability
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function canShip()
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/NED-2421.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $this->writeToLog($logger, __('Verify can create shipment for Order #%1', $this->getIncrementId()));
        if ($this->canUnhold() || $this->isPaymentReview()) {
            $this->writeToLog($logger, __('Order #%1 can unhold or being payment review', $this->getIncrementId()));
            return false;
        }

        if ($this->getIsVirtual() || $this->isCanceled()) {
            $this->writeToLog($logger, __('Order #%1 is virtual or being cancel', $this->getIncrementId()));
            return false;
        }

        if ($this->getActionFlag(self::ACTION_FLAG_SHIP) === false) {
            $this->writeToLog($logger, __('Order #%1 is not action flag ship', $this->getIncrementId()));
            return false;
        }

        foreach ($this->getAllItems() as $item) {
            if ($item->getQtyToShip() > 0 && !$item->getIsVirtual() && !$item->getLockedDoShip()) {
                return true;
            }
        }
        $this->writeToLog($logger, __('Order #%1 invalid qty or lock_do_ship', $this->getIncrementId()));
        return false;
    }

    /**
     * @param mixed $logger
     * @param mixed $message
     */
    private function writeToLog($logger, $message)
    {
        if ($logger) {
            $logger->info($message);
        }
    }

    /**
     * Reset shipment collection
     */
    public function resetShipmentsCollection()
    {
        $this->_shipments = null;
    }
}
