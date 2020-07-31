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
interface ProfileEmailBuilderInterface
{
    /**
     * @param \Riki\Subscription\Api\GenerateOrder\ProfileEmailOrderInterface[] $items
     * @return $this
     */
    public function setItems(array $items);

    /**
     * @return \Riki\Subscription\Api\GenerateOrder\ProfileEmailOrderInterface[]
     */
    public function getItems();
}
