<?php

namespace Riki\Loyalty\Model\ResourceModel\Reward\Collection;

use Riki\Loyalty\Model\ConsumerDb\ShoppingPoint;

class NestleCoin extends \Magento\Framework\Data\Collection
{
    /**
     * @var \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint
     */
    protected $_consumerDb;

    /**
     * @var \Riki\Loyalty\Helper\Api
     */
    protected $_apiHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * NestlePoint constructor.
     *
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $shoppingPoint
     * @param \Riki\Loyalty\Helper\Api $apiHelper
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $shoppingPoint,
        \Riki\Loyalty\Helper\Api $apiHelper,
        \Magento\Framework\Registry $registry
    )
    {
        parent::__construct($entityFactory);
        $this->_consumerDb = $shoppingPoint;
        $this->_registry = $registry;
        $this->_apiHelper = $apiHelper;
    }

    /**
     * Load data
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }
        $customerCode = $this->_registry->registry('current_customer_code');
        $response = $this->_consumerDb->getPointHistory($customerCode, ShoppingPoint::TYPE_COIN);
        if ($response['error']) {
            return $this;
        }
        $this->_totalRecords = sizeof($response['history']);
        $pointHistory = $this->_apiHelper->addOrderId($response['history']);
        foreach ($pointHistory as $row) {
            $item = $this->getNewEmptyItem();
            $item->addData($row);
            $this->addItem($item);
        }
        $this->_setIsLoaded(true);
        return $this;
    }
}