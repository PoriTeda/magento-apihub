<?php

namespace Riki\Customer\Api\GridIndexer;

interface ItemsInterface
{
    /**
     * @param ItemInterface[] $items
     *
     * @return $this
     */
    public function setItems($items);

    /**
     * @return ItemInterface[]
     */
    public function getItems();
}
