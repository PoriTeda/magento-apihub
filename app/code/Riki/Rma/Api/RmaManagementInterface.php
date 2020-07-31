<?php
namespace Riki\Rma\Api;

interface RmaManagementInterface
{
    /**
     * Get total items amount
     *
     * @param \Magento\Rma\Model\Rma $rma
     *
     * @return float
     */
    public function getReturnedGoodsAmount(\Magento\Rma\Model\Rma $rma);

    /**
     * Save RMA
     *
     * @param \Riki\Rma\Api\Data\RmaInterface $rmaDataObject
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function saveRma(\Riki\Rma\Api\Data\RmaInterface $rmaDataObject);

    /**
     * Save RMA By API request
     *
     * @param \Riki\Rma\Api\Data\NewRmaInterface $rmaDataObject
     * @return \Riki\Rma\Api\Data\NewRmaResultInterface
     */
    public function createRmaByApi(\Riki\Rma\Api\Data\NewRmaInterface $rmaDataObject);
}