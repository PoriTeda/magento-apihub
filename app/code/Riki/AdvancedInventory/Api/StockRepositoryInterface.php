<?php

namespace Riki\AdvancedInventory\Api;

/**
 * @api
 */
interface StockRepositoryInterface
{
    /**
     * @param Data\StockInterface $stock
     * @return mixed
     */
    public function save(\Riki\AdvancedInventory\Api\Data\StockInterface $stock);

    /**
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria
     * @return mixed
     */
    public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria);
}
