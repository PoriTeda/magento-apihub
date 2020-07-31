<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Update\Grid;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Staging\Model\Update\Includes\Retriever as IncludesRetriever;
use Magento\Staging\Model\Update\Source\Status;
use Psr\Log\LoggerInterface as Logger;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult as AbstractSearchResult;
use Magento\Staging\Model\StagingList;
use Magento\Staging\Model\VersionHistoryInterface;
use Magento\Staging\Model\Update\Includes\Hierarchy;

/**
 * SearchResult for updates
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SearchResult extends AbstractSearchResult
{
    /**
     * @var string[]
     */
    protected $fieldsMap = [
        'end_time' => 'rollbacks.start_time'
    ];

    /**
     * @var StagingList
     */
    protected $stagingList;

    /**
     * @var VersionHistoryInterface
     */
    protected $versionHistory;

    /**
     * @var IncludesRetriever
     */
    protected $includes;

    /**
     * @var Hierarchy
     */
    private $hierarchy;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @param StagingList $stagingList
     * @param VersionHistoryInterface $versionHistory
     * @param IncludesRetriever $includes
     * @param Hierarchy $hierarchy
     * @param DateTimeFactory $dateTimeFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable,
        $resourceModel,
        StagingList $stagingList,
        VersionHistoryInterface $versionHistory,
        IncludesRetriever $includes,
        Hierarchy $hierarchy,
        DateTimeFactory $dateTimeFactory = null
    ) {
        $this->stagingList = $stagingList;
        $this->versionHistory = $versionHistory;
        $this->includes = $includes;
        $this->hierarchy = $hierarchy;
        $this->dateTimeFactory = $dateTimeFactory;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * Initialize select to get staging data
     *
     * The method add staging-specific parts to select retrieving staging data:
     * - don't retrieve rollbacks as updates
     * - retrieve staging rollback related to update if exists
     * - don't retrieve update if its rollback started
     * - calculate update status (is active or upcoming)
     *
     * @throws \Zend_Db_Select_Exception if there is some error while creating select
     * @return void
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->where(sprintf('main_table.%s IS NULL', UpdateInterface::IS_ROLLBACK));
        $this->getSelect()->joinLeft(
            ['rollbacks' => $this->getMainTable()],
            sprintf(
                '%s.%s = %s.%s',
                'main_table',
                'rollback_id',
                'rollbacks',
                'id'
            ),
            [
                'end_time' => 'start_time'
            ]
        );
        $dateTime = $this->getDateTimeFactory()->create();
        $this->getSelect()->where(
            $this->getConnection()->prepareSqlCondition(
                sprintf('rollbacks.%s', UpdateInterface::START_TIME),
                [["gteq" => $dateTime->format('Y-m-d H:i:s')], ["null" => true]]
            )
        );
        $this->getSelect()->columns(
            [
                '*',
                'status' => $this->getConnection()->getCheckSql(
                    $this->getConnection()->prepareSqlCondition(
                        'main_table.id',
                        [['gt' => $this->versionHistory->getCurrentId()]]
                    ),
                    Status::STATUS_UPCOMING,
                    Status::STATUS_ACTIVE
                ),
            ]
        );
    }

    /**
     * Add field filter to collection
     *
     * @see self::_getConditionSql for $condition
     *
     * @param string|array $field
     * @param null|string|array $condition
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                $field[$key] = $this->addTableToField($value);
            }
        } else {
            $field = $this->addTableToField($field);
        }

        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Add corresponding table to requested field using fields map
     *
     * @param string $field
     * @return string
     */
    protected function addTableToField($field)
    {
        return isset($this->fieldsMap[$field]) ? $this->fieldsMap[$field] : sprintf('main_table.%s', $field);
    }

    /**
     * @inheritDoc
     */
    protected function _afterLoad()
    {
        $this->prepareData();
        return parent::_afterLoad();
    }

    /**
     * Prepare calculated staging data
     *
     * The method adds calculated (like entities included in update) data to updates
     *
     * @throws \Zend_Db_Select_Exception if there is some error while creating select
     * @return void
     */
    protected function prepareData()
    {
        $includesData = $this->includes->getIncludes($this->getAllIds());
        $includesData = $this->hierarchy->changeIdToLast($includesData);
        foreach ($this->_items as $id => $item) {
            if ($item->getMovedTo()) {
                unset($this->_items[$id]);
                continue;
            }
            $includes = [];
            foreach ($includesData as $includeData) {
                if ($includeData['created_in'] == $id) {
                    if (isset($includes[$includeData['entity_type']])) {
                        $includes[$includeData['entity_type']]['count'] += $includeData['includes'];
                    } else {
                        $includes[$includeData['entity_type']] = [
                            'entityType' => $includeData['entity_type'],
                            'entityLabel' => __($includeData['entity_type']),
                            'count' => $includeData['includes']
                        ];
                    }
                }
            }
            $item->setData('includes', array_values($includes));
        }
    }

    /**
     * @return DateTimeFactory
     * @deprecated 100.1.7
     */
    private function getDateTimeFactory()
    {
        if ($this->dateTimeFactory === null) {
            $this->dateTimeFactory = ObjectManager::getInstance()->get(DateTimeFactory::class);
        }

        return $this->dateTimeFactory;
    }
}
