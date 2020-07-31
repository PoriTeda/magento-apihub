<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */


namespace Amasty\Promo\Plugin\Quote;

class Item
{
    /**
     * @var \Amasty\Promo\Helper\Item
     */
    protected $promoItemHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;


    public function __construct(
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->promoItemHelper = $promoItemHelper;
        $this->scopeConfig = $scopeConfig;
    }

    public function beforeSetPrice(\Magento\Quote\Model\Quote\Item $subject, $value)
    {
        if ($this->promoItemHelper->isPromoItem($subject))
            return [0];
    }

    public function aroundRepresentProduct(
        \Magento\Quote\Model\Quote\Item $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Product $product
    ) {
        if ($proceed($product))
        {
            $productRuleId = $product->getData('ampromo_rule_id');
            $itemRuleId = $this->promoItemHelper->getRuleId($subject);

            return $productRuleId === $itemRuleId;
        }
        else
            return false;
    }

    public function aroundGetMessage(
        \Magento\Quote\Model\Quote\Item $subject,
        \Closure $proceed,
        $string = true)
    {
        $result = $proceed($string);

        if ($this->promoItemHelper->isPromoItem($subject)) {

            $customMessage = $this->scopeConfig->getValue(
                'ampromo/messages/cart_message',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            if ($customMessage) {
                if ($string) {
                    $result .= "\n" . $customMessage;
                } else {
                    $result [] = $customMessage;
                }
            }
        }

        return $result;
    }
}
