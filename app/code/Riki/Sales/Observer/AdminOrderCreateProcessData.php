<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class AdminOrderCreateProcessData implements ObserverInterface
{
    protected $_quoteSession;

    /**
     * @var \Magento\Quote\Model\Quote\Item\Updater
     */
    protected $quoteItemUpdater;

    protected $_salesAdminHelper;

    public function __construct(
        \Magento\Backend\Model\Session\Quote $quoteSession,
        \Magento\Quote\Model\Quote\Item\Updater $quoteItemUpdater,
        \Riki\Sales\Helper\Admin $salesAdminHelper
    ){
        $this->_quoteSession = $quoteSession;
        $this->quoteItemUpdater = $quoteItemUpdater;
        $this->_salesAdminHelper = $salesAdminHelper;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var \Magento\Sales\Model\AdminOrder\Create $order
         */
        $order = $observer->getEvent()->getOrderCreateModel();
        $params = $observer->getEvent()->getRequest();

        if ($this->_salesAdminHelper->isMultipleShippingAddressCart() && !$order->getQuote()->getData('is_multiple_shipping')) {
            $order->getQuote()->setData('is_multiple_shipping',1);
        }
        /**
         * Update quote items
         */
        if ($this->_salesAdminHelper->isMultipleShippingAddressCart()
            && isset($params['update_items']) && $params['update_items']
        ) {
            // click button combine
            if(isset($params['combine_item']) && $params['combine_item']) {
                // combine cart item
                $this->_salesAdminHelper->combineItemsAddress();
            } else {
                // click button split
                $this->_salesAdminHelper->convertQuoteItemToMultipleCase($order);
            }
        }

        //////////// recollect shipping rates after change payment method

        if(
            isset($params['is_switch_payment_request']) &&
            (
                isset($params['payment']['method']) &&
                $params['payment']['method'] != $order->getQuote()->getPayment()->getMethod()
            )
        ){
            $order->collectShippingRates();
        }
    }
}