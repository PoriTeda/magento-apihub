<?php
/**
 * *
 *  Email Marketing
 *
 *  PHP version 7
 *
 *  @category RIKI
 *  @package Riki\EmailMarketing
 *  @author Nestle.co.jp <support@nestle.co.jp>
 *  @license https://opensource.org/licenses/MIT MIT License
 *  @link https://github.com/rikibusiness/riki-ecommerce
 */
 namespace Riki\EmailMarketing\Model\ResourceModel\Queue;
 /**
  * Class Collection
  *
  *  @category RIKI
  *  @package Riki\EmailMarketing
  *  @author Nestle.co.jp <support@nestle.co.jp>
  *  @license https://opensource.org/licenses/MIT MIT License
  *  @link https://github.com/rikibusiness/riki-ecommerce
  */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_date;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->_date = $date;
    }

    /**
     * Initializes collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\EmailMarketing\Model\Queue', 'Riki\EmailMarketing\Model\ResourceModel\Queue');
    }

}