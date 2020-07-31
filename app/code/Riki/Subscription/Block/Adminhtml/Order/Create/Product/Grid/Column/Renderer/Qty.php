<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Block\Adminhtml\Order\Create\Product\Grid\Column\Renderer;

/**
 * Renderer for Qty field in sales create new order search grid
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Qty extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
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
        // Prepare values
        $disabled = '';
        $addClass = '';

        if ($this->_isInactive($row)) {
            $qty = '';
            $disabled = 'disabled="disabled" ';
            $addClass = ' input-inactive';
        } else {
            if($row->getData('fix_qty') > 0){
                $qty = (int)$row->getData('fix_qty');
                $disabled = 'disabled="disabled" ';
                $addClass = ' input-inactive';

                $unitCase = (null != $row->getData('unit_case'))?$row->getData('unit_case'):'EA';
                if('CS' == $unitCase){
                    $unitQty = (null != $row->getData('unit_qty'))?$row->getData('unit_qty'):1;
                    $qty = $qty/$unitQty;
                }
            } else {
                $qty = $row->getData($this->getColumn()->getIndex());
                $qty *= 1;
                if (!$qty) {
                    $qty = '';
                }
            }
        }

        // Compose html
        $html = '<input type="text" ';
        $html .= 'name="' . $this->getColumn()->getId() . '" ';
        $html .= 'value="' . $qty . '" ' . $disabled;
        $html .= 'class="input-text admin__control-text ' . $this->getColumn()->getInlineCss() . $addClass . '" />';

        // Dont support piece-case for bundle product
        $_product = $this->_productRepository->getById($row->getData('entity_id'));
        if('bundle' == $_product->getTypeId()){
            $hiddenCaseDisplay = '<select name="case_display"  class="input-text admin__control-text case_display hidden">';
            $hiddenCaseDisplay .= '<option value="ea" >'.__('EA').'</option>';
            $hiddenCaseDisplay .= '</select>';
            $html .= $hiddenCaseDisplay;
            return $html;
        }

        //support for piece and case
        $caseDisplay = $this->getCaseDisplay($row);

        if('' != $caseDisplay){
            $html .= $caseDisplay;
        }

        $unitQtyForDisplay = $this->getUnitQty($row);

        if('' != $unitQtyForDisplay){
            $html .= $unitQtyForDisplay;
        }

        return $html;
    }

    public function getCaseDisplay($row){

        $_product = $this->_productRepository->getById($row->getData('entity_id'));

        // Prepare values
        $disabled = '';
        $addClass = '';

        if($row->getData('fix_qty') > 0){
            $disabled = 'disabled="disabled" ';
            $addClass = ' input-inactive';
        }

        $html = '<br><select name="case_display" '.$disabled.' class="input-text admin__control-text case_display '.$addClass.'">';

        if($_product->getCaseDisplay() == 1){
            $html .= '<option value="ea" >'.__('EA').'</option>';
        }
        else
        if($_product->getCaseDisplay() == 2){
            $html .= '<option value="cs" >'.__('CS').'('.$this->getUnitConvertPieceCase($_product).' '.__('EA').')</option>';
        }
        else
        if($_product->getCaseDisplay() == 3){
            if($row->getData('fix_qty') > 0){
                $unitCase = (null != $row->getData('unit_case'))?$row->getData('unit_case'):'EA';
                if('EA' == $unitCase){
                    $html .= '<option value="ea" >'.__('EA').'</option>';
                }
                if('CS' == $unitCase){
                    $html .= '<option value="cs" >'.__('CS').'('.$this->getUnitConvertPieceCase($_product).' '.__('EA').')</option>';
                }
            }
            else{
                $html .= '<option value="ea" >'.__('EA').'</option>';
                $html .= '<option value="cs" >'.__('CS').'('.$this->getUnitConvertPieceCase($_product).' '.__('EA').')</option>';
            }
        }
        else{
            $html .= '<option value="ea" >'.__('EA').'</option>';
        }
        $html .= '</select>';

        return $html;
    }

    public function getUnitQty($row){
        $_product = $this->_productRepository->getById($row->getData('entity_id'));

        $html = '<input type="hidden" ';
        $html .= 'name="unit_qty"';
        $html .= 'value="' . $this->getUnitConvertPieceCase($_product) . '" ';
        $html .= 'class="input-text admin__control-text unit_qty" />';

        return $html;
    }

    public function getUnitConvertPieceCase($_product)
    {
        return (null != $_product->getUnitQty())?$_product->getUnitQty():1;
    }
}
