<?php

namespace Riki\Sales\Model;

class Config extends \Magento\Sales\Model\Order\Config
{
    /**
     * {@inheritDoc}
     */
    public function getStatusLabel($code)
    {
        $area = $this->state->getAreaCode();
        $code = $this->maskStatusForArea($area, $code);
        $status = $this->orderStatusFactory->create()->load($code);

        return $status->getStoreLabel();
    }
}