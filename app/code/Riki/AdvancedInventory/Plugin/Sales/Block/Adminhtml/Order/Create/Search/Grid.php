<?php
namespace Riki\AdvancedInventory\Plugin\Sales\Block\Adminhtml\Order\Create\Search;

use Magento\Eav\Model\Entity\Collection\AbstractCollection;

class Grid
{
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface  */
    protected $config;

    /**
     * Grid constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $config
    )
    {
        $this->config = $config;
    }

    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid $subject
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     */
    public function beforeSetCollection(
        $subject,
        $collection)
    {
        $collection->joinField(
            'as_available_qty',
            'advancedinventory_stock',
            new \Zend_Db_Expr(\Riki\AdvancedInventory\Ui\DataProvider\Product\AddAvailableQtyFieldToCollection::generateQtyColumnExpr($this->config->getValue('cataloginventory/item_options/manage_stock'))),
            'product_id=entity_id',
            null,
            'left'
        );

        $collection->getSelect()->joinLeft(
            ['advancedinventory_item'],
            'e.entity_id=advancedinventory_item.product_id',
            [],
            null,
            'left'
        );

        if (!array_key_exists(AbstractCollection::ATTRIBUTE_TABLE_ALIAS_PREFIX . 'qty', $collection->getSelect()->getPart('from'))) {
            $collection->getSelect()->joinLeft(
                [AbstractCollection::ATTRIBUTE_TABLE_ALIAS_PREFIX . 'qty'   =>  'cataloginventory_stock_item'],
                'e.entity_id=' . AbstractCollection::ATTRIBUTE_TABLE_ALIAS_PREFIX . 'qty.product_id',
                [],
                null,
                'left'
            );
        }

        //void reset join left when filter by having instead where
        $collection->getSelect()->where('IF(' . AbstractCollection::ATTRIBUTE_TABLE_ALIAS_PREFIX . 'as_available_qty.id, 1, 1)=?', 1);
        $collection->getSelect()->where('IF(advancedinventory_item.product_id,1,1)=?', 1);
        $collection->getSelect()->where('IF(' . AbstractCollection::ATTRIBUTE_TABLE_ALIAS_PREFIX . 'qty.product_id,1,1)=?', 1);

        $collection->getSelect()->group("e.entity_id");
    }
}
