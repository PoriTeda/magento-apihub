<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Model\Plugin;

class Quote
{
    /**
     * @var \Magento\GoogleTagManager\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\GoogleTagManager\Helper\Data $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Registry $registry
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->registry = $registry;
    }

    /**
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Magento\Quote\Model\Quote $result
     * @return \Magento\Quote\Model\Quote
     */
    public function afterLoad(\Magento\Quote\Model\Quote $subject, $result)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $result;
        }

        $productQtys = [];
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($subject->getAllItems() as $quoteItem) {
            $parentQty = 1;
            switch ($quoteItem->getProductType()) {
                case 'bundle':
                case 'configurable':
                    break;
                case 'grouped':
                    $id = $quoteItem->getOptionByCode('product_type')->getProductId()
                        . '-' . $quoteItem->getProductId();
                    $productQtys[$id] = $quoteItem->getQty();
                    break;
                case 'giftcard':
                    $id = $quoteItem->getId() . '-' . $quoteItem->getProductId();
                    $productQtys[$id] = $quoteItem->getQty();
                    break;
                default:
                    if ($quoteItem->getParentItem()) {
                        $parentQty = $quoteItem->getParentItem()->getQty();
                        $id = $quoteItem->getId() . '-' .
                            $quoteItem->getParentItem()->getProductId() . '-' .
                            $quoteItem->getProductId();
                    } else {
                        $id = $quoteItem->getProductId();
                    }
                    $productQtys[$id] = $quoteItem->getQty() * $parentQty;
            }
        }
        /** prevent from overwriting on page load */
        if (!$this->checkoutSession->hasData(
            \Magento\GoogleTagManager\Helper\Data::PRODUCT_QUANTITIES_BEFORE_ADDTOCART
        )) {
            $this->checkoutSession->setData(
                \Magento\GoogleTagManager\Helper\Data::PRODUCT_QUANTITIES_BEFORE_ADDTOCART,
                $productQtys
            );
        }
        return $result;
    }
}
