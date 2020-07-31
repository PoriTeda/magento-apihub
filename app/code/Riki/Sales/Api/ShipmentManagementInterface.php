<?php

namespace Riki\Sales\Api;

interface ShipmentManagementInterface
{

    /**
     * @param $incrementId
     * @return \Magento\Framework\DataObject
     */
    public function getByIncrementId($incrementId);

}