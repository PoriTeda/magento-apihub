<?php
namespace Riki\Rma\Plugin\Rma\Model\Rma;

class Grid
{
    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * Grid constructor.
     *
     * @param \Riki\Rma\Helper\Data $dataHelper
     */
    public function __construct(
        \Riki\Rma\Helper\Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    /**
     * Additional data for sync into grid
     *
     * @param \Magento\Rma\Model\Rma $subject
     *
     * @return mixed[]
     */
    public function beforeBeforeSave(\Magento\Rma\Model\Rma $subject)
    {
        if ($subject->getId()) {
            return [];
        }
        $this->prepareOrder($subject);
        $this->prepareCustomer($subject);

        return [];
    }

    /**
     * Prepare data from order
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return void
     */
    public function prepareOrder(\Magento\Rma\Model\Rma $rma)
    {
        $order = $rma->getOrder();
        if (!$order || !$order->getId()) {
            return;
        }

        $rma->setData('order_type', $order->getData('riki_type'));
        $rma->setData('payment_status', $order->getData('payment_status'));
        $rma->setData('payment_agent', $order->getData('payment_agent'));

        $shipments = $this->dataHelper->getRmaOrderShipments($rma);
        $paymentDate = null;
        /** @var \Magento\Sales\Model\Order\Shipment $shipment */
        foreach ($shipments as $shipment) {
            if (!$paymentDate) {
                $paymentDate = $shipment->getData('payment_date');
                continue;
            }
            if ($shipment->getData('payment_date') > $paymentDate) {
                $paymentDate = $shipment->getData('payment_date');
            }
        }
        $rma->setData('payment_date', $paymentDate);

        $paymentMethodCode = $this->dataHelper->getRmaOrderPaymentMethodCode($rma);
        if ($paymentMethodCode) {
            $rma->setData('payment_method', $paymentMethodCode);
        }
    }

    /**
     * Prepare data from order
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return void
     */
    public function prepareCustomer(\Magento\Rma\Model\Rma $rma)
    {
        /** @var \Magento\Customer\Model\Data\Customer $customer */
        $customer = $this->dataHelper->getRmaCustomer($rma);
        if (!$customer) {
            return;
        }

        $customerType = $customer->getCustomAttribute('membership');
        if ($customerType) {
            $rma->setData('customer_type', $customer->getCustomAttribute('membership')->getValue());
        }
        $consumerDbId = $customer->getCustomAttribute('consumer_db_id');
        if ($consumerDbId) {
            $rma->setData('consumer_db_id', $consumerDbId->getValue());
        }

    }
}