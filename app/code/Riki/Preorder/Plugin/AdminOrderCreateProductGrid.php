<?php
namespace Riki\Preorder\Plugin;

class AdminOrderCreateProductGrid
{
    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid $subject
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     */
    public function beforeSetCollection(
        $subject,
        $collection)
    {
            $collection->joinField(
                'backorders',
                'cataloginventory_stock_item',
                'backorders',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            );

            $collection->addFieldToFilter('backorders', ['neq'  =>  \Riki\Preorder\Helper\Data::BACKORDERS_PREORDER_OPTION]);

        }
}
