<?php

namespace Riki\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer;


class Qty extends \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer\Qty
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

    /**
     * Qty constructor.
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
        parent::__construct($context, $typeConfig ,$data);

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
        // Prepare values
        $disabled = '';
        $addClass = '';

        if ($this->_isInactive($row)) {
            $qty = '';
            $disabled = 'disabled="disabled" ';
            $addClass = ' input-inactive';
        } else {
            $qty = $row->getData($this->getColumn()->getIndex());
            $qty *= 1;
            if (!$qty) {
                $qty = '';
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

        return $html;
    }

    public function getCaseDisplay($row){

        $_product = $this->_productRepository->getById($row->getData('entity_id'));
        $html = '<br><select name="case_display" class="input-text admin__control-text case_display">';

        if($_product->getCaseDisplay() == 1){
            $html .= '<option value="ea" >'.__('EA').'</option>';
        }
        else
            if($_product->getCaseDisplay() == 2){
                $html .= '<option value="cs" >'.__('CS').'('.$this->getUnitConvertPieceCase($_product).' '.__('EA').')</option>';
            }
            else
                if($_product->getCaseDisplay() == 3){
                    $html .= '<option value="ea" >'.__('EA').'</option>';
                    $html .= '<option value="cs" >'.__('CS').'('.$this->getUnitConvertPieceCase($_product).' '.__('EA').')</option>';
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
