<?php

namespace Riki\CsvOrderMultiple\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\CsvOrderMultiple\Cron\ImportOrders;

class SetImportOrderFlag implements ObserverInterface
{
    /**
     * @var \Riki\DeliveryType\Model\Validator
     */
    protected $deliveryDateValidator;

    /**
     * SetImportOrderFlag constructor.
     * @param \Riki\DeliveryType\Model\Validator $deliveryDateValidator
     */
    public function __construct(
        \Riki\DeliveryType\Model\Validator $deliveryDateValidator
    ) {
        $this->deliveryDateValidator = $deliveryDateValidator;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        $order->setData(ImportOrders::CSV_ORDER_IMPORT_FLAG, $quote->getData(ImportOrders::CSV_ORDER_IMPORT_FLAG));

        $warehouseImportOrder = ImportOrders::IMPORT_ASSIGNED_WAREHOUSE_ID_KEY;
        $order->setData('original_unique_id', $quote->getData('original_unique_id'));
        $order->setData($warehouseImportOrder, $quote->getData($warehouseImportOrder));
    }
}
