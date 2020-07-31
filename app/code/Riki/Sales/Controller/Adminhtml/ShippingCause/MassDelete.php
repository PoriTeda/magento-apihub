<?php

namespace Riki\Sales\Controller\Adminhtml\ShippingCause;

use Riki\Sales\Model\ShippingCause;
use Riki\Sales\Model\ShippingCauseData;

class MassDelete extends MassAction
{
    /**
     * @param ShippingCause $cause
     * @return $this
     */
    protected function massAction(ShippingCause $cause)
    {
        $this->shippingCauseRepository->delete($cause);
        return $this;
    }
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(ShippingCauseData::ACL_DELETE);
    }
}
