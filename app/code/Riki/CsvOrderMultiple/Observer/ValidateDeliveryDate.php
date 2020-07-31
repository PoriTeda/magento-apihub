<?php

namespace Riki\CsvOrderMultiple\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Riki\CsvOrderMultiple\Cron\ImportOrders;

class ValidateDeliveryDate implements ObserverInterface
{
    /**
     * @var \Riki\DeliveryType\Model\Validator
     */
    protected $deliveryDateValidator;

    /**
     * ValidateDeliveryDate constructor.
     * @param \Riki\DeliveryType\Model\Validator $deliveryDateValidator
     */
    public function __construct(
        \Riki\DeliveryType\Model\Validator $deliveryDateValidator
    )
    {
        $this->deliveryDateValidator = $deliveryDateValidator;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        if ($order->getData(ImportOrders::CSV_ORDER_IMPORT_FLAG)) {
            if (!$this->deliveryDateValidator->validateOrderDeliveryDateData($order)) {
                throw new LocalizedException(__('Delivery date of some item(s) are invalid'));
            }
        }
    }
}
