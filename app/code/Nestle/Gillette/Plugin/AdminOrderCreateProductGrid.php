<?php

namespace Nestle\Gillette\Plugin;

class AdminOrderCreateProductGrid
{
    /**
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid $subject
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     */
    public function beforeSetCollection(
        $subject,
        $collection
    ) {
        $collection->joinField(
            'backorders',
            'cataloginventory_stock_item',
            'backorders',
            'product_id=entity_id',
            '{{table}}.stock_id=1',
            'left'
        );

        $collection->addFieldToFilter('backorders', ['neq' => \Nestle\Gillette\Helper\Data::BACKORDERS_GILLETTE_OPTION]);

    }
}
