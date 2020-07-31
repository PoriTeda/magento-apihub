<?php

namespace Riki\Subscription\Plugin\Promo\Helper\Adminhtml;

class Cart
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(\Magento\Framework\Registry $registry){
        $this->registry = $registry;
    }

    /**
     * Force free gift item to IN Stock for case edit profile in adminhtml
     *
     * @param \Riki\Promo\Helper\Adminhtml\Cart $subject
     * @param \Closure $proceed
     * @param \Magento\Catalog\Model\Product $product
     * @param $qtyRequested
     * @param null $quote
     * @return mixed
     */
    public function aroundCheckAvailableQty(
        \Riki\Promo\Helper\Adminhtml\Cart $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Product $product,
        $qtyRequested,
        $quote = null
    ){

        if ($this->registry->registry(\Riki\Subscription\Controller\Adminhtml\Profile\Edit::ADMINHTML_EDIT_PROFILE_FLAG)) {
            return $qtyRequested;
        }

        return $proceed($product, $qtyRequested, $quote);
    }
}