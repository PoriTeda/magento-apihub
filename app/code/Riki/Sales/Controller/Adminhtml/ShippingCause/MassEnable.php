<?php

namespace Riki\Sales\Controller\Adminhtml\ShippingCause;

use Riki\Sales\Model\ShippingCause;
use Riki\Sales\Model\ShippingCauseData;

class MassEnable extends MassAction
{
    /**
     * @param ShippingCause $cause
     * @return $this
     */
    protected function massAction(ShippingCause $cause)
    {
        $cause->setIsActive(true);
        $this->shippingCauseRepository->save($cause);
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(ShippingCauseData::ACL_SAVE);
    }
}
