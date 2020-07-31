<?php
namespace Riki\Sales\Plugin;

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
        $collection->joinAttribute('ph5_description', 'catalog_product/ph5_description', 'entity_id', null, 'left');
    }
}
