<?php
namespace Riki\CatalogRule\Block\Adminhtml\Subscription\Order\Create\Course\Frequency;

class Js extends \Magento\Backend\Block\Template
{
    protected $_template = 'sales/order/create/course/frequency/js.phtml';

    /**
     * Get url which reload product via ajax
     *
     * @return string
     */
    public function getProductReloadUrl()
    {
        return trim($this->_urlBuilder->getUrl('profile/product/index'), '/');
    }
}
