<?php

namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Renderer;

class ReturnWrappingAdjust extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * {@inheritdoc}
     */
    public function _getInputValueElement(\Magento\Framework\DataObject $row)
    {

        return '<div id="returnWrappingItemExpr' . $row->getId() .'" data-array-sum-expr="totalBeforePointAdjustmentGoodsAmountExpr" data-bind="scope: \'returnWrappingItemExpr' . $row->getId() . '\'"><input data-bind="textInput: y" type="text" class="input-text ' .
        $this->getColumn()->getValidateClass() .
        '" name="items[' .
        $row->getId() . '][' . $this->getColumn()->getId() .
        ']" value="' .
        $this->_getInputValue(
            $row
        ) . '"/></div>';
    }
}