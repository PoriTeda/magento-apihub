<?php
namespace Riki\ShipLeadTime\Api;

interface StockStateInterface
{
    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $sku
     * @param $qtyRequested
     * @param null $placeId
     * @return mixed
     */
    public function checkAvailableQty(\Magento\Quote\Model\Quote $quote, $sku, $qtyRequested, $placeId = null);
}
