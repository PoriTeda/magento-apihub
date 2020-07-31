<?php

namespace Riki\Promo\Helper;

class Item extends \Amasty\Promo\Helper\Item
{
    const PROMO_RULE_ID_KEY = 'ampromo_rule_id';

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return mixed
     */
    public function getRuleId(\Magento\Quote\Model\Quote\Item $item)
    {
        if (!$item->hasData('ampromo_rule_id')) {
            $buyRequest = $item->getBuyRequest();

            $ruleId = isset($buyRequest['options']['ampromo_rule_id'])
                ? $buyRequest['options']['ampromo_rule_id'] : null;

            $item->setData(self::PROMO_RULE_ID_KEY, $ruleId);
        }

        return $item->getData(self::PROMO_RULE_ID_KEY);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     *
     * @return bool
     */
    public function isPromoItem(\Magento\Quote\Model\Quote\Item $item)
    {
        return $this->getRuleId($item) !== null;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     *
     * @return int|null
     */
    public function getRuleIdByOrderItem(\Magento\Sales\Model\Order\Item $item)
    {
        $buyRequest = $item->getBuyRequest();
        return isset($buyRequest['options']['ampromo_rule_id']) ? $buyRequest['options']['ampromo_rule_id'] : null;
    }
}
