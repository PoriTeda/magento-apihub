<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\AdvancedInventory\Model\Queue\Schema;

/**
 * @codeCoverageIgnore
 */
class OosQueueSchema implements \Riki\AdvancedInventory\Model\Queue\OosQueueSchemaInterface
{
    /**
     * @var string|int $outOfStockId
     */
    private $outOfStockId;

    /**
     * {@inheritdoc}
     * @return $this
     */
    public function setOosModelId($outOfStockId)
    {
        $this->outOfStockId = $outOfStockId;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @return string|int $outOfStockId
     */
    public function getOosModelId()
    {
        return $this->outOfStockId;
    }
}
