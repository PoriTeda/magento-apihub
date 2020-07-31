<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Products\Hanpukai\Column;


class UnitCase extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
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
        $this->typeConfig = $typeConfig;

        $this->_productRepository = $productRepository;
    }

    /**
     * Render product qty field
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $html  = '<div class="admin__grid-control">';

        // Dont support piece-case for bundle product
        $_product = $this->_productRepository->getById($row->getData('entity_id'));

        if('bundle' == $_product->getTypeId()){
            return $html;
        }

        //support for piece and case
        $caseDisplay = $this->getCaseDisplay($row);

        if('' != $caseDisplay){
            $html .= $caseDisplay;
        }

        $unitQty = $this->getUnitQty($row);

        if('' != $unitQty){
            $html .= $unitQty;
        }
        $html .= '</div>';
        return $html;
    }

    public function getCaseDisplay($row){

        $_product = $this->_productRepository->getById($row->getData('entity_id'));

        $html = '<select name="unit_case" class="input-text admin__control-text unit_case">';

        if($_product->getCaseDisplay() == 1){
            $html .= '<option value="ea" >'.__('EA').'</option>';
        }
        else
            if($_product->getCaseDisplay() == 2){
                $html .= '<option value="cs" >'.__('CS').'('.$this->getUnitConvertPieceCase($_product).' '.__('EA').')</option>';
            }
            else
                if($_product->getCaseDisplay() == 3){

                    $isEASelected = (strtoupper($row->getData('unit_case')) == 'EA')?'selected':'';

                    $html .= '<option '.$isEASelected.' value="ea" >'.__('EA').'</option>';
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
        $unitQty = (null != $_product->getUnitQty())?$_product->getUnitQty():1;
        if(1 == $_product->getCaseDisplay()){
            $unitQty = 1;
        }
        return $unitQty;
    }
}
