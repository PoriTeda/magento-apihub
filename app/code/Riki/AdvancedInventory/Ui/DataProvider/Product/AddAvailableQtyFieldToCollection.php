<?php
namespace Riki\AdvancedInventory\Ui\DataProvider\Product;

use Magento\Framework\Data\Collection;
use Magento\Ui\DataProvider\AddFieldToCollectionInterface;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;

class AddAvailableQtyFieldToCollection implements AddFieldToCollectionInterface
{
    const TABLE_NAME =  AbstractCollection::ATTRIBUTE_TABLE_ALIAS_PREFIX . 'as_available_qty';

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface  */
    protected $config;

    /**
     * AddAvailableQtyFieldToCollection constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $config
    )
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function addField(Collection $collection, $field, $alias = null)
    {

        $collection->joinField(
            'as_available_qty',
            'advancedinventory_stock',
            new \Zend_Db_Expr(self::generateQtyColumnExpr($this->config->getValue('cataloginventory/item_options/manage_stock'))),
            'product_id=entity_id',
            null,
            'left'
        );

        $collection->getSelect()->joinLeft(
            ['advancedinventory_item' => 'advancedinventory_item'],
            'e.entity_id=advancedinventory_item.product_id',
            []
        );

        //void reset join left when filter by having instead where
        $collection->getSelect()->where('IF(' . AbstractCollection::ATTRIBUTE_TABLE_ALIAS_PREFIX . 'as_available_qty.id, 1, 1)=?', 1);
        $collection->getSelect()->where('IF(advancedinventory_item.product_id,1,1)=?', 1);
        $collection->getSelect()->where('IF(' . AbstractCollection::ATTRIBUTE_TABLE_ALIAS_PREFIX . 'qty.product_id,1,1)=?', 1);

        $collection->getSelect()->group("e.entity_id");
    }

    /**
     * @param $manageStockConfig
     * @return string
     */
    static public function generateQtyColumnExpr($manageStockConfig)
    {
        return 'SUM(
            IF(
                IF(' . AbstractCollection::ATTRIBUTE_TABLE_ALIAS_PREFIX . 'qty.use_config_manage_stock,' . $manageStockConfig . ',' . AbstractCollection::ATTRIBUTE_TABLE_ALIAS_PREFIX . 'qty.manage_stock), 
                IF(
                    advancedinventory_item.multistock_enabled, 
                    IF(
                        ' . self::TABLE_NAME . '.manage_stock=1,
                        ' . self::TABLE_NAME .'.quantity_in_stock+(
                            IF(
                                ' . self::TABLE_NAME .'.use_config_setting_for_backorders<>1 AND ' . self::TABLE_NAME .'.backorder_allowed>0 AND ' . self::TABLE_NAME .'.backorder_expire>=CURDATE(),
                                ' . self::TABLE_NAME .'.backorder_limit,
                                0
                            )
                        ),
                        0
                    ),
                    ' . AbstractCollection::ATTRIBUTE_TABLE_ALIAS_PREFIX . 'qty.qty
                ),
                0
            )	
        )';
    }
}
