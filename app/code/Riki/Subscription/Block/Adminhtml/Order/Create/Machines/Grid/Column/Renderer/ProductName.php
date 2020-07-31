<?php
namespace Riki\Subscription\Block\Adminhtml\Order\Create\Machines\Grid\Column\Renderer;

class ProductName extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * Type config
     *
     * @var \Magento\Catalog\Model\ProductTypes\ConfigInterface
     */
    protected $typeConfig;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $typeConfig
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $typeConfig,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->typeConfig = $typeConfig;

        $this->productRepository = $productRepository;
    }

    /**
     * Render product qty field
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $product = $this->productRepository->getById($row->getData('product_id'));
        $rendered = parent::render($product);
        $isConfigurable = $product->canConfigure();
        $style = $isConfigurable ? '' : 'disabled';
        $prodAttributes = $isConfigurable ? sprintf(
            'list_type = "product_to_add" product_id = %s',
            $row->getId()
        ) : 'disabled="disabled"';
        return sprintf(
                '<a href="javascript:void(0)" class="action-configure %s" %s>%s</a>',
                $style,
                $prodAttributes,
                __('Configure')
            ) . $rendered;
        if (!$product->getId()) {
            $name = '';
        } else {
            $name = $product->getName();
        }
        return $name;
    }
}
