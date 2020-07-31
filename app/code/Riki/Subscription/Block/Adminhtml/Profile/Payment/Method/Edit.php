<?php
namespace Riki\Subscription\Block\Adminhtml\Profile\Payment\Method;

class Edit extends \Riki\Subscription\Block\Frontend\Profile\Payment\Method\Edit
{
    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function _beforeToHtml()
    {
        return $this;
    }

    /**
     * Get submit url
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('profile/profile/payment_method_save');
    }
}