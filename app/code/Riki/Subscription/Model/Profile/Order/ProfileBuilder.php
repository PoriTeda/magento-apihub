<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Model\Profile\Order;

use Riki\Subscription\Api\GenerateOrder\ProfileBuilderInterface;

/**
 * @codeCoverageIgnore
 */
class ProfileBuilder implements ProfileBuilderInterface
{
    /**
     * @var []
     */
    private $items;

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function setItems(array $items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getItems()
    {
        return $this->items;
    }
}
