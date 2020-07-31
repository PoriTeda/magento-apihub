<?php
namespace Riki\Promo\Plugin\Promo\Model\Rule\Action\Discount;

use Riki\Sales\Model\Config\Source\OrderType as OrderChargeType;

class AbstractDiscount
{
    /**
     * @var \Magento\SalesRule\Model\Rule\Action\Discount\DataFactory
     */
    protected $discountDataFactory;

    /**
     * AbstractDiscount constructor.
     * @param \Magento\SalesRule\Model\Rule\Action\Discount\DataFactory $discountDataFactory
     */
    public function __construct(
        \Magento\SalesRule\Model\Rule\Action\Discount\DataFactory $discountDataFactory
    )
    {
        $this->discountDataFactory = $discountDataFactory;
    }

    /**
     * @param \Amasty\Promo\Model\Rule\Action\Discount\AbstractDiscount $subject
     * @param \Closure $proceed
     * @param $rule
     * @param $item
     * @param $qty
     * @return mixed
     */
    public function aroundCalculate(
        \Amasty\Promo\Model\Rule\Action\Discount\AbstractDiscount $subject,
        \Closure $proceed,
        $rule,
        $item,
        $qty
    )
    {
        if ($item instanceof \Magento\Quote\Model\Quote\Item) {
            $quote = $item->getQuote();

            if (
                $quote instanceof \Magento\Quote\Model\Quote &&
                in_array($quote->getData('charge_type'), [OrderChargeType::ORDER_TYPE_FREE_SAMPLE, OrderChargeType::ORDER_TYPE_REPLACEMENT])
            ) {
                return $this->discountDataFactory->create();
            }
        }

        return $proceed($rule, $item, $qty);
    }
}
