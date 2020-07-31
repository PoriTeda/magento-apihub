<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class CheckFreeGiftAfterReorder implements ObserverInterface
{
    protected $_checkoutSession;

    /**
     * @var \Amasty\Promo\Helper\Item
     */
    protected $_promoItemHelper;

    /**
     * @var \Amasty\Promo\Model\Registry
     */
    protected $_promoRegistry;

    /**
     * @var \Amasty\Promo\Helper\Cart
     */
    protected $_promoCartHelper;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_cart;

    public function __construct(
        \Magento\Checkout\Model\Session $session,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Magento\Checkout\Model\Cart $cart,
        \Amasty\Promo\Helper\Cart $promoCartHelper,
        \Amasty\Promo\Model\Registry $promoRegistry
    ){
        $this->_checkoutSession = $session;
        $this->_promoRegistry = $promoRegistry;
        $this->_promoItemHelper = $promoItemHelper;
        $this->_cart = $cart;
        $this->_promoCartHelper = $promoCartHelper;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $this->_cart->getQuote();

        $items = $quote->getAllItems();
        $quote->getItemsCollection()->removeAllItems();

        foreach ($items as $item) {
            $quote->getItemsCollection()->addItem($item);
        }

        $allowedItems = $this->_promoRegistry->getPromoItems();

        $isUpdate = false;

        foreach ($quote->getAllItems() as $item) {
            if ($this->_promoItemHelper->isPromoItem($item)) {
                if ($item->getParentItemId())
                    continue;

                $sku = $item->getProduct()->getData('sku');

                $ruleId = $this->_promoItemHelper->getRuleId($item);

                if (isset($allowedItems['_groups'][$ruleId])) { // Add one of

                    if ($allowedItems['_groups'][$ruleId]['qty'] <= 0) {
                        $this->_cart->removeItem($item->getId())->save();
                    }
                }
                else if (isset($allowedItems[$sku])) { // Add all of

                    if ($allowedItems[$sku]['qty'] <= 0) {
                        $this->_cart->removeItem($item->getId())->save();
                    }
                }
                else {
                    $this->_cart->removeItem($item->getId())->save();
                }
            }
        }

        if($isUpdate){
            $this->_promoCartHelper->updateQuoteTotalQty(false, $quote);
        }
    }
}