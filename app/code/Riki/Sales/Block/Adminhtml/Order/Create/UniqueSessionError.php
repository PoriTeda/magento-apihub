<?php

namespace Riki\Sales\Block\Adminhtml\Order\Create;

class UniqueSessionError extends \Magento\Framework\View\Element\Template
{
    /**
     * @return string
     */
    protected function _toHtml()
    {
        return $this->getUrl('*');
    }
}
