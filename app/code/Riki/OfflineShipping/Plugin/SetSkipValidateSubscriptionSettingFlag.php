<?php

namespace Riki\OfflineShipping\Plugin;

class SetSkipValidateSubscriptionSettingFlag
{
    /**
     * Free shipping rules don't need to validate subscription settings.
     *
     * @param \Magento\OfflineShipping\Model\SalesRule\Calculator $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     *
     * @return mixed
     */
    public function aroundProcessFreeShipping(
        \Magento\OfflineShipping\Model\SalesRule\Calculator $subject,
        \Closure $proceed,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item
    )
    {
        $item->setData('skip_validate_subscription_setting_flag', true);
        $result = $proceed($item);
        $item->unsetData('skip_validate_subscription_setting_flag');
        return $result;
    }
}
