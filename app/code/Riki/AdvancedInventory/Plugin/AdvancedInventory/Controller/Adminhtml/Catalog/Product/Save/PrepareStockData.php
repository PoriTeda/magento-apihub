<?php
namespace Riki\AdvancedInventory\Plugin\AdvancedInventory\Controller\Adminhtml\Catalog\Product\Save;

class PrepareStockData
{
    /**
     * @param \Magento\Catalog\Controller\Adminhtml\Product\Save $subject
     * @return array|void
     */
    public function beforeExecute(
        \Magento\Catalog\Controller\Adminhtml\Product\Save $subject
    ) {
        if ($subject->getRequest()->getParam('id') == null) {
            return;
        }

        $postData = $subject->getRequest()->getPostValue();

        if (isset($postData['inventory'])) {
            $postData['product']['stock_data']['original_inventory_qty'] = null;

            $subject->getRequest()->setPostValue('product', $postData['product']);
        }

        return [];
    }
}