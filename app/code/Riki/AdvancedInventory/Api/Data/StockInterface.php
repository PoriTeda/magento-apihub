<?php

namespace Riki\AdvancedInventory\Api\Data;

/**
 * @api
 */
interface StockInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    const QTY = 'quantity_in_stock';

    /**
     * @return mixed
     */
    public function getQty();

    /**
     * @param $qty
     * @return mixed
     */
    public function setQty($qty);
}
