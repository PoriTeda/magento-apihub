<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Nestle\Gillette\Block\Adminhtml\Sales\Order\Create\Gillette\Grid\Column\Renderer;

/**
 * Renderer for Qty field in sales create new order search grid
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Checked extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    /**
     * Type config
     *
     * @var \Magento\Catalog\Model\ProductTypes\ConfigInterface
     */
    protected $typeConfig;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $typeConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $typeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->typeConfig = $typeConfig;
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
        $disabled = '';
        if ($this->_isInactive($row)) {
            $disabled = 'disabled="disabled" ';
        }
        // Compose html
        $html = '<label class="data-grid-checkbox-cell-inner" for="id_'.$row->getId().'">';
        $html .= '<input type="checkbox" ';
        $html .= 'value="'.$row->getId().'"';
        $html .= 'id="id_'.$row->getId().'"';
        $html .= 'class="checkbox" '.$disabled.' />';
        $html .= '</label>';
        return $html;
    }
}
