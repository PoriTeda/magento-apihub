<?php
namespace Riki\AdvancedInventory\Plugin\CatalogInventory\Model\Quote\Item;

class QuantityValidator
{
    protected $_helper;

    /**
     * @param \Riki\AdvancedInventory\Helper\Data $helper
     */
    public function __construct(
        \Riki\AdvancedInventory\Helper\Data $helper
    ){
        $this->_helper = $helper;
    }

    /**
     * validate bundle child stock
     *
     * @param \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\Event\Observer $observer
     * @return mixed
     */
    public function aroundValidate(
        \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator $subject,
        \Closure $proceed,
        \Magento\Framework\Event\Observer $observer
    )
    {
        $result = $proceed($observer);

        /* @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $observer->getEvent()->getItem();

        if ($quoteItem->getProductType() != \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE ||
            !$quoteItem ||
            !$quoteItem->getProductId() ||
            !$quoteItem->getQuote() ||
            $quoteItem->getQuote() instanceof \Riki\Subscription\Model\Emulator\Cart ||
            $quoteItem->getQuote()->getIsSuperMode()
        ) {
            return $result;
        }

        $errorInfos = $quoteItem->getErrorInfos();

        foreach($errorInfos as $errorInfo){
            if($errorInfo['code'] == \Magento\CatalogInventory\Helper\Data::ERROR_QTY)
                return $result;
        }

        if(!$this->_helper->isInStockBundleItem($quoteItem)){
            $quoteItem->addErrorInfo(
                'cataloginventory',
                \Riki\AdvancedInventory\Helper\Data::ERROR_QTY_BUNDLE_CHILDREN,
                __('The product %1 is out of stock.',$quoteItem->getProduct()->getName())
            );
            $quoteItem->getQuote()->addErrorInfo(
                'stock',
                'cataloginventory',
                \Riki\AdvancedInventory\Helper\Data::ERROR_QTY_BUNDLE_CHILDREN,
                __('Some of the products are out of stock.')
            );
        }else{
            // Delete error from item and its quote, if it was set due to item out of stock
            $params = ['origin' => 'cataloginventory', 'code' => \Riki\AdvancedInventory\Helper\Data::ERROR_QTY_BUNDLE_CHILDREN];

            if($quoteItem->getHasError()){
                $quoteItem->removeErrorInfosByParams($params);
            }

            if($quoteItem->getQuote()->getHasError()){
                $quoteItem->getQuote()->removeErrorInfosByParams(null, $params);
            }

        }

        return $result;
    }
}