<?php

namespace Riki\Sales\Controller\Adminhtml\ShippingReason;

use Riki\Sales\Model\ShippingReason;
use Riki\Sales\Model\ShippingReasonData;

class MassEnable extends MassAction
{
    /**
     * @param ShippingReason $reason
     * @return $this
     */
    protected function massAction(ShippingReason $reason)
    {
        $reason->setIsActive(true);
        $this->shippingReasonRepository->save($reason);
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(ShippingReasonData::ACL_SAVE);
    }
}
