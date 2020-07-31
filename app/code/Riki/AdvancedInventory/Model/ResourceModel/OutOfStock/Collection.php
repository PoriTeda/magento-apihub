<?php
namespace Riki\AdvancedInventory\Model\ResourceModel\OutOfStock;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Riki\AdvancedInventory\Model\OutOfStock::class,
            \Riki\AdvancedInventory\Model\ResourceModel\OutOfStock::class
        );
    }

    /**
     * Set store id for each collection item when collection was loaded
     *
     * @return $this
     */
    public function _afterLoad()
    {
        parent::_afterLoad();
        foreach ($this->_items as $item) {
            $additionalData = $item->getData('additional_data');

            if ($additionalData) {
                $item->setData('additional_data', json_decode($additionalData, true));
            }
        }
        return $this;
    }

    /**
     * Get invisible order ids
     *
     * @param $customerId
     * @return array
     */
    public function getInvisibleOrderIdsByCustomerId($customerId)
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable(), ['generated_order_id'])
            ->where('customer_id = ?', $customerId)
            ->where('generated_order_id IS NOT NULL')
            ->where('prize_id IS NOT NULL');

        return $this->getConnection()->fetchCol($select);
    }
}
