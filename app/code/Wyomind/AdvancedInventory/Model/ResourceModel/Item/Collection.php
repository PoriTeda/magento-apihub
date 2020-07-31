<?php

namespace Wyomind\AdvancedInventory\Model\ResourceModel\Item;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * Define resource model
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Wyomind\AdvancedInventory\Model\Item', 'Wyomind\AdvancedInventory\Model\ResourceModel\Item');
    }

    public function updateAfterPosUpdate(
        $defaultStockManagement,
        $defaultUseDefaultSettingForBackorder,
        $defaultAllowBackorder,
        $placeId
    ) {

        $connection = $this->_resource;
        $advancedinventoryStock = $connection->getTable("advancedinventory_stock");

        $fields = ["item_id", "product_id", "place_id", "manage_stock", "use_config_setting_for_backorders", "backorder_allowed"];

        $this->addFieldToSelect(["product_id" => "product_id"])->getSelect()
                ->columns(
                    [
                    'place_id' => new \Zend_Db_Expr($placeId),
                    'default_stock_management' => new \Zend_Db_Expr($defaultStockManagement),
                    'default_use_default_setting_for_backorder' => new \Zend_Db_Expr($defaultUseDefaultSettingForBackorder),
                    'default_allow_backorder' => new \Zend_Db_Expr($defaultAllowBackorder)
                    ]
                );
        $sql = $this->getSelect()->insertFromSelect(['advancedinventory_stock' => $advancedinventoryStock], $fields, true);
        return $this->getConnection()->query($sql);
    }
}
