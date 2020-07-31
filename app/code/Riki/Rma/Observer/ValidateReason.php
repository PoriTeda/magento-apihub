<?php
namespace Riki\Rma\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ValidateReason implements ObserverInterface
{
    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Magento\Rma\Controller\Adminhtml\Rma\SaveNew
     */
    protected $saveNewAction;

    /**
     * @var \Magento\Framework\Message\Manager
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * ValidateReason constructor.
     * @param \Magento\Framework\Message\Manager $messageManager
     * @param \Magento\Rma\Controller\Adminhtml\Rma\SaveNew $saveNewAction
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Message\Manager $messageManager,
        \Magento\Rma\Controller\Adminhtml\Rma\SaveNew $saveNewAction,
        \Riki\Rma\Helper\Data $dataHelper,
        \Magento\Framework\Registry $registry
    ) {
        $this->messageManager = $messageManager;
        $this->saveNewAction = $saveNewAction;
        $this->dataHelper = $dataHelper;
        $this->registry = $registry;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->registry->registry('rma_save_more_refund_data')) {
            return;
        }
        /** @var \Magento\Rma\Model\Rma $rma */
        $rma = $observer->getRma();

        if (!$rma->dataHasChangedFor('reason_id')) {
            return;
        }

        if ($rma->getId()) {
            // exist rma need reset all return amount
            $rma->setData('total_cancel_point', null);
            $rma->setData('total_return_point', null);
            $rma->setData('return_shipping_fee', null);
            $rma->setData('return_payment_fee', null);
            $rma->setData('total_return_amount', null);
            $rma->setData('total_cancel_point_adjusted', null);
            $rma->setData('total_return_point_adjusted', null);
            $rma->setData('return_shipping_fee_adjusted', null);
            $rma->setData('return_payment_fee_adjusted', null);
            $rma->setData('total_return_amount_adjusted', null);
            $items = $rma->getItems();
            if ($items) {
                /** @var \Magento\Rma\Model\Item $item */
                foreach ($items as $item) {
                    $item->setData('return_amount', null);
                    $item->setData('return_wrapping_fee', null);
                }
            }
        }

        $notAllowed = $this->dataHelper->getReasonCODNotAllowed();
        if (!$notAllowed) {
            return;
        }

        $order = $this->dataHelper->getRmaOrder($rma);
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return;
        }

        $methodCode = $this->dataHelper->getRmaOrderPaymentMethodCode($rma);
        if ($methodCode != \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
            return;
        }

        $paymentStatus = [
            \Riki\Shipment\Model\ResourceModel\Status\Options\Payment::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
            \Riki\Shipment\Model\ResourceModel\Status\Options\Payment::SHIPPING_PAYMENT_STATUS_NESTLE_COLLECTED
        ];

        $isPaid = in_array($order->getData('payment_status'), $paymentStatus);

        $reason = $this->dataHelper->getRmaReason($rma);
        if (in_array($reason->getData('code'), $notAllowed) && $isPaid) {
            $this->messageManager->addWarning(
                __('The selected reason code is invalid for an order that has been paid. ' .
                'Please confirm reason code and payment status')
            );
        } elseif (!in_array($reason->getData('code'), $notAllowed) && !$isPaid) {
            $this->messageManager->addWarning(
                __('The selected reason code is invalid for an order that has not been paid. ' .
                'Please confirm reason code and payment status')
            );
        }

        $this->saveNewAction->getResponse()
            ->setRedirect($this->saveNewAction->getUrl('adminhtml/*/edit', [
                'id' => $rma->getId()
            ]));
    }
}
