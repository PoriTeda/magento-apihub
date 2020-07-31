<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */



namespace Riki\Sales\Api;

/**
 * Order status interface.
 *
 * @api
 */
interface OrderManagementInterface
{
    /**
     * Get the status for a specified order.
     *
     * @param string $id The Machine Maintenance Order ID
     *
     * @return \Riki\Sales\Api\Data\OrderStatusInterface
     */
    public function getStatus($id);

    /**
     * Get items
     * @param
     * @return \Magento\CatalogInventory\Api\Data\OrderApiItemInterface[]
     */
    public function getItems();

    /**
     * Set items
     *
     * @param \Magento\CatalogInventory\Api\Data\OrderApiItemInterface[] $items
     * @return $this
     */
    public function setItems(array $items);

    /**
     * @param $incrementId
     * @return \Magento\Framework\DataObject
     */
    public function getByIncrementId($incrementId);

}