<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\AdvancedInventory\Model\Queue;

/**
 * Interface OosQueueSchemaInterface
 * @package Riki\AdvancedInventory\Model\Queue
 */
interface OosQueueSchemaInterface
{
    /**
     * @param string|int $outOfStockId
     * @return $this
     */
    public function setOosModelId($outOfStockId);

    /**
     * @return string|int $outOfStockId
     */
    public function getOosModelId();
}
