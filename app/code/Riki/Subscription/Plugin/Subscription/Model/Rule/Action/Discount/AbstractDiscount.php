<?php

namespace Riki\Subscription\Plugin\Subscription\Model\Rule\Action\Discount;

class AbstractDiscount
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
     * @param \Riki\Subscription\Model\Rule\Action\Discount\AbstractDiscount $subject
     * @param \Closure $proceed
     * @param \Magento\Catalog\Model\Product $product
     * @return bool|mixed
     */
    public function aroundValidateProductInStock(
        \Riki\Subscription\Model\Rule\Action\Discount\AbstractDiscount $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Product $product
    ){

        if ($this->registry->registry(\Riki\Subscription\Controller\Adminhtml\Profile\Edit::ADMINHTML_EDIT_PROFILE_FLAG)) {
            return true;
        }

        return $proceed($product);
    }
}