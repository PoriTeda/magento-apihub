<?php

namespace Riki\StockPoint\Api;

interface StockPointManagementInterface
{
    /**
     * Deactivate stock point
     * @param string $stockPointId
     * @return \Riki\StockPoint\Api\Data\DeactivateStockPointResponseInterface
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function deactivate($stockPointId);

    /**
     * @param string $profileId
     * @param string $nextDeliveryDate
     * @param string $deliveryTimeSlot
     * @param string $isReject
     * @return \Riki\StockPoint\Api\Data\StopStockPointResponseInterface
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function stopStockPoint($profileId, $nextDeliveryDate, $deliveryTimeSlot, $isReject);
    
    /**
     * Update stockpoint via API
     * @param \Riki\StockPoint\Api\Data\StockPointProfileUpdateInputDataInterface $inputData
     * @return mixed
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function updateProfileStockpoint(
        \Riki\StockPoint\Api\Data\StockPointProfileUpdateInputDataInterface $inputData
    );
}
