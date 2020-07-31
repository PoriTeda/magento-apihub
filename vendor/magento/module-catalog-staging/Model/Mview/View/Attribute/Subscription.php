<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Mview\View\Attribute;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\DB\Ddl\Trigger;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Class Subscription implements statement building for staged entity attribute subscription
 *
 * @package Magento\CatalogStaging\Model\Mview\View\Attribute
 */
class Subscription extends \Magento\Framework\Mview\View\Subscription
{
    /**
     * @var \Magento\Framework\EntityManager\EntityMetadata
     */
    protected $entityMetadata;

    /**
     * Save state of Subscription for build statement for retrieving entity id value
     *
     * @var array
     */
    private $statementState = [];

    /**
     * List of columns that can be updated in a subscribed table
     * without creating a new change log entry
     *
     * @var array
     */
    private $ignoredUpdateColumns = [];

    /**
     * @param ResourceConnection $resource
     * @param \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory
     * @param \Magento\Framework\Mview\View\CollectionInterface $viewCollection
     * @param \Magento\Framework\Mview\ViewInterface $view
     * @param string $tableName
     * @param string $columnName
     * @param MetadataPool $metadataPool
     * @param string|null $entityInterface
     * @param array $ignoredUpdateColumns
     * @throws \Exception
     */
    public function __construct(
        ResourceConnection $resource,
        \Magento\Framework\DB\Ddl\TriggerFactory $triggerFactory,
        \Magento\Framework\Mview\View\CollectionInterface $viewCollection,
        \Magento\Framework\Mview\ViewInterface $view,
        $tableName,
        $columnName,
        MetadataPool $metadataPool,
        $entityInterface = null,
        $ignoredUpdateColumns = []
    ) {
        parent::__construct(
            $resource,
            $triggerFactory,
            $viewCollection,
            $view,
            $tableName,
            $columnName,
            $ignoredUpdateColumns
        );
        $this->ignoredUpdateColumns = $ignoredUpdateColumns;
        $this->entityMetadata = $metadataPool->getMetadata($entityInterface);
    }

    /**
     * Build trigger statement for INSERT, UPDATE, DELETE events
     *
     * @param string $event
     * @param \Magento\Framework\Mview\View\ChangelogInterface $changelog
     * @return string
     */
    protected function buildStatement($event, $changelog)
    {
        $triggerBody = '';

        switch ($event) {
            case Trigger::EVENT_INSERT:
            case Trigger::EVENT_UPDATE:
                $eventType = 'NEW';
                break;
            case Trigger::EVENT_DELETE:
                $eventType = 'OLD';
                break;
            default:
                return $triggerBody;
        }
        $entityIdHash = $this->entityMetadata->getIdentifierField()
            . $this->entityMetadata->getEntityTable()
            . $this->entityMetadata->getLinkField()
            . $event;
        if (!isset($this->statementState[$entityIdHash])) {
            $triggerBody = $this->buildEntityIdStatementByEventType($eventType);
            $this->statementState[$entityIdHash] = true;
        }

        $trigger = $this->buildStatementByEventType($changelog);
        if ($event == Trigger::EVENT_UPDATE) {
            $trigger = $this->addConditionsToTrigger($trigger);
        }
        $triggerBody .= $trigger;

        return $triggerBody;
    }

    /**
     * Adds quoted conditions to the trigger
     *
     * @param string $trigger
     * @return string
     */
    private function addConditionsToTrigger(string $trigger): string
    {
        $tableName = $this->resource->getTableName($this->getTableName());
        if ($this->connection->isTableExists($tableName)
            && $describe = $this->connection->describeTable($tableName)
        ) {
            $columnNames = array_column($describe, 'COLUMN_NAME');
            $columnNames = array_diff($columnNames, $this->ignoredUpdateColumns);
            if ($columnNames) {
                $columns = [];
                foreach ($columnNames as $columnName) {
                    $columns[] = sprintf(
                        'NEW.%1$s <=> OLD.%1$s',
                        $this->connection->quoteIdentifier($columnName)
                    );
                }
                $trigger = sprintf(
                    "IF (%s) THEN %s END IF;",
                    implode(' OR ', $columns),
                    $trigger
                );
            }
        }

        return $trigger;
    }

    /**
     * Build trigger body
     *
     * @param string $eventType
     * @return string
     */
    private function buildEntityIdStatementByEventType($eventType): string
    {
        return vsprintf(
            'SET @entity_id = (SELECT %1$s FROM %2$s WHERE %3$s = %4$s.%3$s);',
            [
                $this->connection->quoteIdentifier(
                    $this->entityMetadata->getIdentifierField()
                ),
                $this->connection->quoteIdentifier(
                    $this->resource->getTableName($this->entityMetadata->getEntityTable())
                ),
                $this->connection->quoteIdentifier(
                    $this->entityMetadata->getLinkField()
                ),
                $eventType
            ]
        ) . PHP_EOL;
    }

    /**
     * Build sql statement for trigger
     *
     * @param \Magento\Framework\Mview\View\ChangelogInterface $changelog
     * @return string
     */
    private function buildStatementByEventType($changelog): string
    {
        return vsprintf(
            'INSERT IGNORE INTO %1$s (%2$s) values(@entity_id);',
            [
                $this->connection->quoteIdentifier(
                    $this->resource->getTableName($changelog->getName())
                ),
                $this->connection->quoteIdentifier(
                    $changelog->getColumnName()
                ),
            ]
        );
    }
}
