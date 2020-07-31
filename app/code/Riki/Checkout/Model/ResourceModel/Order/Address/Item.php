<?php
namespace Riki\Checkout\Model\ResourceModel\Order\Address;

use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;

/**
 * Quote address item resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Item extends AbstractDb
{
    /**
     * Main table and field initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('order_address_item', 'address_item_id');
    }

    /**
     * @param array $orderItemIds
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteItemByOrderItemsId(array $orderItemIds){

        if(count($orderItemIds))
            $this->getConnection()->delete($this->getMainTable(), ['order_item_id IN (?)' => $orderItemIds]);

        return $this;
    }
}

