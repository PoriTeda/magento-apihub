<?php

namespace Riki\Preorder\Plugin;

class ProductEditTabInventory
{
    protected $helper;

    public function __construct(\Riki\Preorder\Helper\Data $helper)
    {
        $this->helper = $helper;
    }

    public function aroundToHtml(\Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Inventory $subject,\Closure $closure)
    {
        $html = $closure();
        $preorderHtml = $subject->getLayout()->createBlock('Riki\Preorder\Block\Adminhtml\Product\Edit\Tab\Inventory\PreOrder')->toHtml();

        $preorderJsHtml = $subject->getLayout()->createBlock('Riki\Preorder\Block\Adminhtml\Product\Edit\Tab\Inventory\PreOrderJs')->setTemplate('product_inventory_js.phtml')->toHtml();

        $html .= $preorderHtml . PHP_EOL . $preorderJsHtml;
        return $html;

    }
}
