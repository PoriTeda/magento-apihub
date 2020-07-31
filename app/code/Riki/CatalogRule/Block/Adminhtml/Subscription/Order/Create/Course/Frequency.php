<?php
namespace Riki\CatalogRule\Block\Adminhtml\Subscription\Order\Create\Course;

class Frequency extends \Riki\Subscription\Block\Adminhtml\Order\Create\Course\Frequency
{
    /**
     * @inheritdoc
     */
    protected function _afterToHtml($html)
    {
        $block = $this->getLayout()->createBlock('Riki\CatalogRule\Block\Adminhtml\Subscription\Order\Create\Course\Frequency\Js');

        return parent::_afterToHtml($html) . $block->toHtml();
    }
}
