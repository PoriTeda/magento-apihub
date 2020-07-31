<?php

namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\MultiMachines\Column;

class Sku extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Catalog\Model\ProductFactory $productFactory,
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->productFactory = $productFactory;
        $this->request = $context->getRequest();
    }

    /**
     * Render product field
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $productId = $row->getData('product_id');
        $product = $this->productFactory->create()->load($productId);
        if (!$product->getData()) {
            return;
        }

        $html = '<input type="text" name="sku" value="'.$product->getSku().'" class="input-text ">';

        return $html;
    }
}
