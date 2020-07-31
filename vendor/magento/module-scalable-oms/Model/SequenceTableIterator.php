<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ScalableOms\Model;

use Magento\Framework\App\ResourceConnection;

/**
 * This iterator iterates sales sequence tables.
 * For split databases default connection is used.
 */
class SequenceTableIterator implements \OuterIterator
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Iterator
     */
    private $internalIterator;

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @param ResourceConnection $resourceConnection
     * @param string $connectionName
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        $connectionName = ResourceConnection::DEFAULT_CONNECTION
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->connectionName = $connectionName;
    }

    /**
     * {@inheritdoc}
     */
    public function getInnerIterator()
    {
        if (!$this->internalIterator) {
            $connection = $this->resourceConnection->getConnection($this->connectionName);
            $sequenceMetaTable = $this->resourceConnection->getTableName('sales_sequence_meta');
            $sequenceTables = [];
            $fetchedData = $connection->query(
                $connection->select()->from(
                    $sequenceMetaTable,
                    ['entity_type', 'store_id']
                )
            )->fetchAll();
            foreach ($fetchedData as $sequenceTableData) {
                $sequenceTables[] = sprintf(
                    'sequence_%s_%s',
                    $sequenceTableData['entity_type'],
                    $sequenceTableData['store_id']
                );
            }

            $this->internalIterator = new \ArrayIterator($sequenceTables);
        }

        return $this->internalIterator;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->resourceConnection->getTableName($this->getInnerIterator()->current());
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->getInnerIterator()->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->getInnerIterator()->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->getInnerIterator()->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->getInnerIterator()->rewind();
    }
}
