<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\Sales\Model\Config\Source\OrderType as ChargeType;

class ConvertOrderToQuote implements ObserverInterface
{
    protected $_salesAdminHelper;

    protected $_adminQuoteSession;

    protected $_orderChargeType;

    public function __construct(
        \Riki\Sales\Helper\Admin $salesAdminHelper,
        ChargeType $orderType,
        \Magento\Backend\Model\Session\Quote $adminQuoteSession
    ){
        $this->_salesAdminHelper = $salesAdminHelper;
        $this->_adminQuoteSession = $adminQuoteSession;
        $this->_orderChargeType = $orderType;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        /** @var \Magento\Quote\Model\Quote $quote */
        $order = $observer->getOrder();
        $quote = $observer->getQuote();

        if($order->getIsMultipleShipping()){
            $this->_adminQuoteSession->setData(\Riki\Sales\Helper\Admin::DELIVERY_ORDER_TYPE_SESSION_NAME, \Riki\Sales\Model\Config\DeliveryOrderType::MULTIPLE_ADDRESS);

            $productIdToAddressId = $this->_adminQuoteSession->getData('edit_order_products_address_ids');

            if(!is_array($productIdToAddressId))
                $productIdToAddressId = [];

            foreach($quote->getAllItems() as $item){
                /**
                 * @var \Magento\Quote\Model\Quote\Item $item
                 */
                $qty = $item->getQty();

                $unitQty = 1;
                if ('CS' == $item->getUnitCase()) {
                    $unitQty = ($item->getUnitQty() != null) ? $item->getUnitQty() : 1;
                }

                if($qty/$unitQty > 1 && !$item->getParentItemId()){

                    for($_i=0; $_i<($qty/$unitQty-1); $_i++){

                        $addressId = 0;

                        // update item address
                        foreach($productIdToAddressId as $key   => $productIdAddressId){
                            if($productIdAddressId[0] == $item->getProductId()){
                                $addressId = $productIdAddressId[1];
                                unset($productIdToAddressId[$key]);
                                break;
                            }
                        }

                        $newItem = clone $item;
                        $newItem->setQty($unitQty);
                        $newItem->setAddressId($addressId);
                        $quote->addItem($newItem);

                        if($item->getHasChildren() && (!$newItem->getHasChildren() || count($newItem->getChildren()) == 0)){
                            foreach ($item->getChildren() as $child) {
                                $newChild = clone $child;
                                $newChild->setParentItem($newItem);
                                $quote->addItem($newChild);
                            }
                        }
                    }

                    // update item address
                    foreach($productIdToAddressId as $key   => $productIdAddressId){
                        if($productIdAddressId[0] == $item->getProductId()){
                            $item->setAddressId($productIdAddressId[1]);
                            unset($productIdToAddressId[$key]);
                            break;
                        }
                    }

                    $item->setQty($unitQty);
                    $order->setRecollect(true);
                }
            }
        }

        $this->_adminQuoteSession->unsetData('edit_order_products_address_ids');

        $chargeType = $order->getChargeType();

        if(!in_array($chargeType, array_keys($this->_orderChargeType->toArray())))
            $chargeType = ChargeType::ORDER_TYPE_NORMAL;

        $this->_salesAdminHelper->processOrderWithChargeType($chargeType);

        $quote->setData('charge_type', $chargeType);

        $additionalFields = [
            'order_channel',
            'campaign_id',
            'original_order_id',
            'siebel_enquiry_id',
            'replacement_reason',
            'free_samples_wbs',
            'customer_firstnamekana',
            'customer_lastnamekana'
        ];

        foreach($additionalFields as $field){
            if($order->hasData($field) && $order->getData($field) !== null){
                $quote->setData($field, $order->getData($field));
            }
        }

        $quote->save();

    }
}