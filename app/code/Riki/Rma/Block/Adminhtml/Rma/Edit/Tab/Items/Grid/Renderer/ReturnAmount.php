<?php

namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Renderer;

class ReturnAmount extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $amountHelper;

    /**
     * ReturnAmount constructor.
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Rma\Helper\Amount $amountHelper,
        \Magento\Backend\Block\Context $context,
        array $data = []
    )
    {
        $this->amountHelper = $amountHelper;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\DataObject $row
     *
     * @return int
     */
    public function _getInputValue(\Magento\Framework\DataObject $row)
    {
        return $this->amountHelper->getReturnAmountByItem($row);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\DataObject $row
     *
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        return $this->_getInputValueElement($row);
    }

    /**
     * {@inheritdoc}
     */
    public function _getInputValueElement(\Magento\Framework\DataObject $row)
    {
        return '<span>' . intval($this->_getInputValue($row)) . '</span><input type="hidden" class="input-text ' .
        $this->getColumn()->getValidateClass() .
        '" name="items[' .
        $row->getId() . '][' . $this->getColumn()->getId() .
        ']" value="' .
        $this->_getInputValue(
            $row
        ) . '"/>' .
        '<script type="text/x-magento-init">
    {
        "*": {
            "Magento_Ui/js/core/app": {
                "components": {
                    "returnAmountItemExpr' . $row->getId() . '": {
                        "component": "Riki_Rma/js/view/rma/lib/sum-expr",
                        "_x": ' . intval($this->_getInputValue($row)) . ',
                        "_y": ' . intval($row->getData('return_amount_adj')) .'
                    }
                }
            }
        }
    }
</script>';
    }
}