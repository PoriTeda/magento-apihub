<?php

namespace Riki\Loyalty\Model\ResourceModel\Reward;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'reward_id';
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registryManager;

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null

    ) {
        $this->_registryManager = $registry;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Initialize resource model for collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Riki\Loyalty\Model\Reward', 'Riki\Loyalty\Model\ResourceModel\Reward');
    }

    /**
     * @param string $customerCode
     * @return $this
     */
    public function addCustomerFilter($customerCode)
    {
        if ($customerCode) {
            $this->getSelect()->where(
                'customer_code = ?',
                $customerCode
            );
        } else {
            $this->getSelect()->where(
                'customer_code IS NULL'
            );
        }
        return $this;
    }
}