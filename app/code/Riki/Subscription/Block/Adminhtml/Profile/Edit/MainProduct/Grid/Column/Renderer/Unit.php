<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Block\Adminhtml\Profile\Edit\MainProduct\Grid\Column\Renderer;

/**
 * Renderer for Qty field in sales create new order search grid
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Unit extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

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

        $this->_productRepository = $productRepository;
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
        /*Compose html*/
        if ($row->getData('case_display') == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY) {
            $unitCase = \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE;
        } else {
            $unitCase = \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE;
        }
        $html = '<input type="hidden" ';
        $html .= 'class="input-text" ';
        $html .= 'name="unit_qty" ';
        $html .= 'value="'.($row->getData('unit_qty')?$row->getData('unit_qty'):1).'" />';
        $html .= '<input type="hidden" ';
        $html .= 'class="input-text" ';
        $html .= 'name="unit_case" ';
        $html .= 'value="'.$unitCase.'" />';
        $html .= '<div>'.$unitCase.'</div>';

        return $html;
    }
}
