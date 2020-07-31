<?php
namespace Riki\Subscription\Block\Adminhtml\Order\Create\Machines\Grid\Column\Renderer;

class Checked extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
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
     * Checked constructor.
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
     * Returns whether this qty field must be inactive
     *
     * @param \Magento\Framework\DataObject $row
     * @return bool
     */
    protected function _isInactive($row)
    {
        return $this->typeConfig->isProductSet($row->getTypeId());
    }

    /**
     * Render product qty field
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        // Prepare values
        $disabled = '';
        $product = $this->productRepository->getById($row->getProductId());
        if ($this->_isInactive($product) || !$product->getQuantityAndStockStatus()['is_in_stock']) {
            $disabled = 'disabled="disabled" ';
            $addClass = ' input-inactive';
        } else {
            if ($product->getData('fix_qty') > 0) {
                $disabled = 'disabled="disabled" ';
            }
        }
        // Compose html
        $html = '<label class="data-grid-checkbox-cell-inner" for="id_'.$row->getTypeId().'_'.$row->getProductId().'">';
        $html .= '<input type="checkbox" ';
        $html .= 'machine="true"';
        $html .= "data-readonly=\"". $row->getTypeId() . "\"";
        $html .= 'value="'.$row->getTypeId().'_'.$row->getProductId().'"';
        $html .= 'id="id_'.$row->getTypeId().'_'.$row->getProductId().'"';
        $html .= 'class="checkbox" '.$disabled.' />';
        $html .= '</label>';
        return $html;
    }
}
