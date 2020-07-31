<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Api\GenerateOrder;

/**
 * Interface ProfileBuilderInterface
 * @package Riki\Subscription\Api\GenerateOrder
 */
interface ProfileBuilderInterface
{
    /**
     * @param \Riki\Subscription\Api\GenerateOrder\ProfileOrderInterface[] $items
     * @return $this
     */
    public function setItems(array $items);

    /**
     * @return \Riki\Subscription\Api\GenerateOrder\ProfileOrderInterface[]
     */
    public function getItems();
}
