<?php

namespace Riki\Customer\Model\GridIndexer;

use Riki\Customer\Api\GridIndexer\ItemInterface;
use Riki\Customer\Api\GridIndexer\ItemInterfaceFactory;
use Riki\Customer\Api\GridIndexer\ItemsInterface;
use Riki\Customer\Api\GridIndexer\ItemsInterfaceFactory;

class ItemsBuilder
{
    /**
     * @var ItemInterfaceFactory
     */
    protected $itemFactory;

    /**
     * @var ItemsInterfaceFactory
     */
    protected $itemsFactory;

    public function __construct(
        ItemInterfaceFactory $itemFactory,
        ItemsInterfaceFactory $itemsFactory
    )
    {
        $this->itemFactory = $itemFactory;
        $this->itemsFactory = $itemsFactory;
    }

    /**
     * @param array $items
     * @return ItemsInterface
     */
    public function build(array $items)
    {
        $objectItems = [];

        foreach ($items as $customerId) {
            /** @var $item ItemInterface */
            $item = $this->itemFactory->create();
            $item->setCustomerId($customerId);
            $objectItems[] = $item;
        }

        /** @var $reindexItems ItemsInterface */
        $reindexItems = $this->itemsFactory->create();
        $reindexItems->setItems($objectItems);

        return $reindexItems;
    }
}
