<?php
namespace Riki\Promo\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesQuoteItemSetCustomAttribute implements ObserverInterface
{
    const VISIBLE_USER_ACCOUNT_DEFAULT_VALUE = 1;

    /**
     * @var \Riki\Promo\Helper\Data
     */
    protected $_promoDataHelper;

    /**
     * @param \Riki\Promo\Helper\Data $dataHelper
     */
    public function __construct(
        \Riki\Promo\Helper\Data $dataHelper
    ){
        $this->_promoDataHelper = $dataHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quoteItem = $observer->getQuoteItem();
        // NED-3114: set default value to visible_user_account
        $quoteItem->setVisibleUserAccount(self::VISIBLE_USER_ACCOUNT_DEFAULT_VALUE);
        if(!$this->_promoDataHelper->isVisibleFreeGiftInUserAccountItem($quoteItem)){
            $quoteItem->setVisibleUserAccount(0);
        }
    }
}