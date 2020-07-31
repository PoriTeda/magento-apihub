<?php
namespace Riki\Catalog\Model\ResourceModel;

class ProductStatus extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Riki\Subscription\Logger\LoggerReplaceProduct
     */
    protected $_logger;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Riki\Subscription\Logger\LoggerReplaceProduct $replaceLogger,
        $connectionName = null
    ) {
        $this->_logger = $replaceLogger;
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {

        $this->_init('riki_product_stock_status', 'status_id');
    }

    /**
     * Replace product feature 2.8
     *
     * @param int $oldId Product ID
     * @param int $newId Product ID
     *
     * @return $this
     * @throws \Exception
     */
    public function replaceProductInCategory($oldId, $newId)
    {
        $productTable = $this->getTable('catalog_category_product');
        $connection = $this->getConnection();

        // select course will update
        $catIds = $connection->fetchCol($connection->select()
            ->from($productTable, ['category_id'])
            ->where('product_id=?', $oldId));

        if (sizeof($catIds)) {
            try {
                $connection->beginTransaction();

                $this->removeDuplicateProductWith('category_id', $productTable, $oldId, $newId);
                // update
                $connection->update(
                    $productTable,
                    ['product_id' => $newId],
                    ['product_id=?' => $oldId]
                );
                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e;
            }
        }

        return $this;
    }

    protected function removeDuplicateProductWith($key, $table, $oldId, $newId)
    {
        $connection = $this->getConnection();
        $oldKeyIds = $connection->fetchCol(
            $connection->select()
                ->from($table, [$key])
                ->where('product_id=?', $oldId)
        );
        $newKeyIds = $connection->fetchCol(
            $connection->select()
                ->from($table, [$key])
                ->where('product_id=?', $newId)
        );
        $removeIds = array_intersect($oldKeyIds, $newKeyIds);
        if (sizeof($removeIds)) {
            $connection->delete(
                $table,
                [$key . ' IN (?)' => $removeIds, 'product_id = ?' => $oldId]
            );
        }
    }
}