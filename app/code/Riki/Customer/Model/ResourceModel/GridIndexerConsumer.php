<?php

namespace Riki\Customer\Model\ResourceModel;

use Magento\Framework\Indexer\IndexerRegistry;
use Riki\Customer\Api\GridIndexer\ItemsInterface;

class GridIndexerConsumer
{
    protected $indexerRegistry;

    /**
     * GridIndexerConsumer constructor.
     *
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        IndexerRegistry $indexerRegistry
    )
    {
        $this->indexerRegistry = $indexerRegistry;
    }

    /**
     * @param ItemsInterface $message
     */
    public function execute(ItemsInterface $message)
    {
        $customerIds = [];

        foreach ($message->getItems() as $item) {
            $customerIds[] = $item->getCustomerId();
        }

        if ($customerIds) {
            $indexer = $this->indexerRegistry->get(\Magento\Customer\Model\Customer::CUSTOMER_GRID_INDEXER_ID);
            $indexer->reindexList($customerIds);
        }
    }
}
