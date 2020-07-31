<?php

namespace Riki\Sales\Observer;

class BlockInvoiceOrder implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Riki\Customer\Helper\ShoshaHelper
     */
    protected $shoshaHelper;

    /**
     * BlockInvoiceOrder constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Customer\Helper\ShoshaHelper $shoshaHelper
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Riki\Customer\Helper\ShoshaHelper $shoshaHelper
    ) {
        $this->registry = $registry;
        $this->shoshaHelper = $shoshaHelper;
    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /*get order data from observer*/
        $order = $observer->getEvent()->getOrder();

        /*do not need to check and block order for simulate subscription case */
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return;
        }

        /*block create new order by back order logic*/
        if($quote = $this->registry->registry('quote_admin')) {
            if ($quote->getData('is_generate')) {
                return;
            }
        }

        /**
         * Ticket 9144
         * Performance api
         * Don't process for machine api
         */
        if($order->getOrderChannel() == 'machine_maintenance') {
            return;
        }

        /*block create new order if current customer is blocked by shosha business*/
        $this->blockOrderByShoshaBusiness($order);
    }

    /**
     * Block create new order if current customer is blocked by Shosha business
     *
     * @param $order
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function blockOrderByShoshaBusiness($order)
    {
        if ( !empty($order->getPayment()) &&
                $order->getPayment()->getMethod() == \Riki\Sales\Model\Order\PaymentMethod::PAYMENT_METHOD_INVOICED
        ) {
            if ($this->shoshaHelper->isBlockInvoiceOrder($order)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('You can not proceed the order with payment invoice. Please contact the call center.'));
            }
        }
    }
}
