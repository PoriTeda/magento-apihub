<?php

namespace Riki\Subscription\Block\Adminhtml\Simulate\Order;

class View
    extends \Magento\Sales\Block\Order\View
{
    /**
     * @return \Riki\Subscription\Model\Profile\Profile
     */
    public function getCurrentProfile(){
        return $this->_coreRegistry->registry('current_profile');
    }
}