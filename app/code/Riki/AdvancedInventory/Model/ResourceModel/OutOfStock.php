<?php
namespace Riki\AdvancedInventory\Model\ResourceModel;

class OutOfStock extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct() //@codingStandardsIgnoreLine
    {
        $this->_init('riki_advancedinventory_outofstock', 'entity_id');
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $this->_setChildrenData($object);

        return parent::_afterSave($object); // TODO: Change the autogenerated stub
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        $additionalData = $object->getData('additional_data');

        if ($additionalData) {
            $object->setData('additional_data', json_decode($additionalData, true));
        }

        return parent::_afterLoad($object);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $additionalData = $object->getData('additional_data');

        if (is_array($additionalData)) {
            $object->setData('additional_data', json_encode($additionalData));
        }

        return parent::_beforeSave($object);
    }

    /**
     * @return string
     */
    protected function _getChildrenTable()
    {
        return $this->getTable('riki_advancedinventory_outofstock_children');
    }

    /**
     * save bundle children qty
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _setChildrenData(\Magento\Framework\Model\AbstractModel $object)
    {
        $childrenData = $object->getData('quote_item_children_qty');

        if (is_array($childrenData) && count($childrenData)) {

            $insertedData = [];

            foreach ($childrenData as $childData) {
                foreach ($childData as $productId    =>  $qty) {
                    $insertedData[] = [
                        'parent_id' =>  $object->getId(),
                        'product_id'    =>  $productId,
                        'qty'   =>  $qty
                    ];
                }
            }

            $this->getConnection()->insertOnDuplicate($this->_getChildrenTable(), $insertedData);
        }

        return $this;
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return array
     */
    public function getChildrenQty(\Magento\Framework\Model\AbstractModel $object)
    {
        $result = [];

        $query = $this->getConnection()->select()
            ->from($this->_getChildrenTable())
            ->where('parent_id=?', $object->getId());

        $queryResult = $this->getConnection()->fetchAll($query);

        foreach ($queryResult as $item) {
            $result[] = [$item['product_id']    =>  $item['qty']];
        }

        return $result;
    }

    /**
     * @param $productId
     * @return array
     */
    public function getOutOfStockIdsByProductId($productId)
    {
        $select = $this->getConnection()->select()
            ->from($this->_getChildrenTable(), 'parent_id')
            ->where('product_id=?', $productId)
            ->distinct();

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    public function clearChildrenQty(\Magento\Framework\Model\AbstractModel $object)
    {
        $condition = [
            'parent_id = ?' => $object->getId()
        ];

        $this->getConnection()->delete($this->_getChildrenTable(), $condition);
    }

    /**
     * Get list generated order ids by original order id
     *
     * @param $origOrderId
     *
     * @return array
     */
    public function getGenOrderIdsByOrigOrderId($origOrderId)
    {
        $conn = $this->getConnection();
        $select = $conn->select()
            ->from($this->getMainTable(), ['generated_order_id'])
            ->where('original_order_id = ?', $origOrderId)
            ->where('generated_order_id > 0');

        return $conn->fetchCol($select);
    }

    /**
     * @param $generatedOrderId
     * @return array
     */
    public function getOosByGeneratedOrderId($generatedOrderId)
    {
        $conn = $this->getConnection();
        $select = $conn->select()
            ->from($this->getMainTable(), ['generated_order_id'])
            ->where('generated_order_id = ?', $generatedOrderId);

        return $conn->fetchRow($select);
    }
}
