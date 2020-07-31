<?php
namespace Riki\Questionnaire\Model\ResourceModel\Questionnaire;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Riki\Questionnaire\Model\ResourceModel\Question;

/**
 * Class Collection
 * @package Riki\Questionnaire\Model\ResourceModel\Questionnaire
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'enquete_id';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_date;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date
     * @param mixed $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->_date = $date;
    }

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Riki\Questionnaire\Model\Questionnaire',
            'Riki\Questionnaire\Model\ResourceModel\Questionnaire'
        );
    }

    /**
     * @return $this
     */
    public function addDateFilter(){
        $now = $this->_date->date()->format('Y-m-d');

        $this->getSelect()->where(
            'start_date is null or start_date <= ?',
            $now
        )->where(
            'end_date is null or end_date >= ?',
            $now
        );

        return $this;
    }

}