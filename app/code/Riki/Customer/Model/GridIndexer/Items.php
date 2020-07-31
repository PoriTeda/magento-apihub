<?php

namespace Riki\Customer\Model\GridIndexer;

use Riki\Customer\Api\GridIndexer\ItemInterface;
use Riki\Customer\Api\GridIndexer\ItemsInterface;

class Items implements ItemsInterface
{
    /**
     * @var ItemInterface[]
     */
    private $items;

    /**
     * @param ItemInterface[] $items
     *
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return ItemInterface[]
     */
    public function getItems()
    {
        return $this->items;
    }
}
