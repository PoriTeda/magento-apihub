<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Riki\AdvancedInventory\Model\ResourceModel;

class Stock extends \Wyomind\AdvancedInventory\Model\ResourceModel\Stock
{
    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @var \Magento\Framework\App\ResourceConnection\ConnectionFactory
     */
    protected $connectionFactory;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * Stock constructor.
     *
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\App\ResourceConnection\ConnectionFactory $connectionFactory
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param null $connectionName
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\App\ResourceConnection\ConnectionFactory $connectionFactory,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->functionCache = $functionCache;
        $this->connectionFactory = $connectionFactory;
        $this->deploymentConfig = $deploymentConfig;
        parent::__construct($context, $connectionName);
    }


    /**
     * Lock products stock for update
     *
     * @param array $items
     * @param array $placeIds
     *
     * @return array
     */
    public function lockProductsStocks(array $items, $placeIds = [])
    {
        if (empty($items)) {
            return [];
        }

        $conn = $this->getTransactionConnection();
        $stockTb = $conn->getTableName('advancedinventory_stock');
        $select = $conn->select()
            ->from($stockTb)
            ->where('product_id IN (?)', $items);

        if ($placeIds) {
            $select->where('place_id IN (?)', $placeIds);
        }

        $select->forUpdate(true);

        return $conn->fetchAll($select);
    }

    /**
     * Get new connection to db
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getNewConnection()
    {
        $config = $this->deploymentConfig->get('db/connection/default');
        return $this->connectionFactory->create($config);
    }

    /**
     * Get connection which used to begin transaction. Need transaction level 1 to minimize lock time
     *
     * @return false|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getTransactionConnection()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        $conn = $this->getConnection();
        if ($conn->getTransactionLevel() != 0) {
            $conn = $this->getNewConnection();
        }

        $this->functionCache->store($conn);

        return $conn;
    }

    /**
     * +/- stock for product
     *
     * @param $items
     * @param $placeId
     * @param $operator
     *
     * @return $this
     */
    public function correctItemsQty($items, $placeId, $operator)
    {
        if (empty($items)) {
            return $this;
        }

        $conn = $this->getTransactionConnection();
        $conditions = [];
        foreach ($items as $productId => $qty) {
            $case = $conn->quoteInto('?', $productId);
            $result = $conn->quoteInto("quantity_in_stock{$operator}?", $qty);
            $conditions[$case] = $result;
        }

        $value = $conn->getCaseSql('product_id', $conditions, 'quantity_in_stock');
        $where = ['product_id IN (?)' => array_keys($items), 'place_id = ?' => $placeId];

        $stockTb = $conn->getTableName('advancedinventory_stock');
        $conn->beginTransaction();
        $conn->update($stockTb, ['quantity_in_stock' => $value], $where);
        $conn->commit();

        return $this;
    }
}
