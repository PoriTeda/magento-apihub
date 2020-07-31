<?php

namespace Riki\Loyalty\Plugin\Pricing;

use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\Pricing\Price\PriceInterface;

class RendererPool
{
    /**
     * @param $subject
     * @param \Closure $proceed
     * @param AmountInterface $amount
     * @param SaleableInterface|null $saleableItem
     * @param PriceInterface|null $price
     * @param array $data
     * @return AmountRenderInterface
     */
    public function aroundCreateAmountRender(
        $subject,
        \Closure $proceed,
        AmountInterface $amount,
        SaleableInterface $saleableItem = null,
        PriceInterface $price = null,
        array $data = []
    )
    {
        /** @var \Magento\Framework\View\Element\Template $amountBlock */
        $amountBlock = $proceed($amount, $saleableItem, $price, $data);
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $amountBlock->getSaleableItem();

        $unitCase = \Riki\CreateProductAttributes\Model\Product\UnitEc::UNIT_PIECE;
        $unitQty = 1;

        if($product && $product->getData('case_display')){
            if(\Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY == $product->getData('case_display')){
                $unitCase = \Riki\CreateProductAttributes\Model\Product\UnitEc::UNIT_CASE;
                $unitQty = (null != $product->getData('unit_qty'))?$product->getData('unit_qty'):1;
            }
            $amountBlock->setData('unit_case', $unitCase);
            $amountBlock->setData('unit_qty',  $unitQty);
        }

        if ($product && ($percent = $product->getData('point_currency'))) {
            $amount = $amountBlock->getAmount();
            $price = floor($amount->getValue('tax') * $percent/100);
            $price = $price * $unitQty;
            $amountBlock->setData('point_earn', floor($price));
        }

        return $amountBlock;
    }
}