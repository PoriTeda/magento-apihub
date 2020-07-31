<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */


namespace Amasty\Promo\Plugin;

class CalculatorFactory
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    protected $classByType = [
        \Amasty\Promo\Model\Rule::SAME_PRODUCT  => 'Amasty\Promo\Model\Rule\Action\Discount\SameProduct',
        \Amasty\Promo\Model\Rule::PER_PRODUCT   => 'Amasty\Promo\Model\Rule\Action\Discount\Product',
        \Amasty\Promo\Model\Rule::WHOLE_CART    => 'Amasty\Promo\Model\Rule\Action\Discount\Cart',
        \Amasty\Promo\Model\Rule::SPENT         => 'Amasty\Promo\Model\Rule\Action\Discount\Spent',
    ];

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->_objectManager = $objectManager;
    }

    public function aroundCreate(
        \Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory $subject,
        \Closure $proceed,
        $type
    ) {
        if (isset($this->classByType[$type])) {
            return $this->_objectManager->create($this->classByType[$type]);
        }
        else
            return $proceed($type);
    }
}
