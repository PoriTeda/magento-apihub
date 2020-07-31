<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Block\Adminhtml\Catalog\Product\Composite\Fieldset;

/**
 * @api
 * @since 100.0.2
 */
class Giftcard extends \Magento\GiftCard\Block\Catalog\Product\View\Type\Giftcard
{
    /**
     * Checks whether block is last fieldset in popup
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsLastFieldset()
    {
        if ($this->hasData('is_last_fieldset')) {
            return $this->getData('is_last_fieldset');
        } else {
            return !$this->getProduct()->getOptions();
        }
    }

    /**
     * Get current currency code
     *
     * @param null|string|bool|int|\Magento\Store\Model\Store $storeId $storeId
     * @return string
     * @codeCoverageIgnore
     */
    public function getCurrentCurrencyCode($storeId)
    {
        return $this->_storeManager->getStore($storeId)->getCurrentCurrencyCode();
    }
}
