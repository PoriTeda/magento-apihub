<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */

namespace Amasty\Promo\Block;

class Add extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Amasty\Promo\Helper\Data
     */
    protected $promoHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = [],
        \Amasty\Promo\Helper\Data $promoHelper
    ) {
        parent::__construct($context, $data);

        $this->promoHelper = $promoHelper;
    }

    protected function _toHtml()
    {
        $items = $this->promoHelper->getNewItems();

        if ($items)
            return parent::_toHtml();
        else
            return '';
    }

    public function getMessage()
    {
        $message = $this->_scopeConfig->getValue(
            'ampromo/messages/add_message',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $message;
    }

    public function isOpenAutomatically()
    {
        $auto = $this->_scopeConfig->isSetFlag(
            'ampromo/messages/auto_open_popup',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $auto;
    }
}
