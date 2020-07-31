<?php
namespace Riki\Subscription\Block\Adminhtml\Order\Create\Machines\Grid\Column\Renderer;

class Qty extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
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
        $product = $this->productRepository->getById($row['product_id']);
        if (!$product->getId()) {
            return;
        }
        if ($this->_isInactive($product)) {
            $qty = '';
            $disabled = 'disabled="disabled" ';
            $addClass = ' input-inactive';
        } else {
            if ($product->getData('fix_qty') > 0) {
                $qty = (int)$product->getData('fix_qty');
                $disabled = 'disabled="disabled" ';
                $addClass = ' input-inactive';

                $unitCase = (null != $product->getData('unit_case'))?$product->getData('unit_case'):'EA';
                if ('CS' == $unitCase) {
                    $unitQty = (null != $product->getData('unit_qty'))?$product->getData('unit_qty'):1;
                    $qty = $qty/$unitQty;
                }
            } else {
                $qty = $product->getData($this->getColumn()->getIndex());
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

        if ('bundle' == $product->getTypeId()) {
            $hiddenCaseDisplay = '<select name="case_display" ';
            $hiddenCaseDisplay .= 'class="input-text admin__control-text case_display hidden">';
            $hiddenCaseDisplay .= '<option value="ea" >'.__('EA').'</option>';
            $hiddenCaseDisplay .= '</select>';
            $html .= $hiddenCaseDisplay;
            return $html;
        }

        //support for piece and case
        $caseDisplay = $this->getCaseDisplay($row);

        if ('' != $caseDisplay) {
            $html .= $caseDisplay;
        }

        $unitQtyForDisplay = $this->getUnitQty($row);

        if ('' != $unitQtyForDisplay) {
            $html .= $unitQtyForDisplay;
        }

        return $html;
    }

    public function getCaseDisplay($row)
    {
        $product = $this->productRepository->getById($row->getData('product_id'));

        // Prepare values
        $disabled = '';
        $addClass = '';

        if ($product->getData('fix_qty') > 0) {
            $disabled = 'disabled="disabled" ';
            $addClass = ' input-inactive';
        }

        $html = '<br><select name="case_display" '.$disabled;
        $html .= ' class="input-text admin__control-text case_display '.$addClass.'">';

        if ($product->getCaseDisplay() == 1) {
            $html .= '<option value="ea" >'.__('EA').'</option>';
        } elseif ($product->getCaseDisplay() == 2) {
            $html .= '<option value="cs" >'.__('CS').'(';
            $html .= $this->getUnitConvertPieceCase($product).' '.__('EA').')</option>';
        } elseif ($product->getCaseDisplay() == 3) {
            if ($product->getData('fix_qty') > 0) {
                $unitCase = (null != $product->getData('unit_case'))?$product->getData('unit_case'):'EA';
                if ('EA' == $unitCase) {
                    $html .= '<option value="ea" >'.__('EA').'</option>';
                }
                if ('CS' == $unitCase) {
                    $html .= '<option value="cs" >'.__('CS').'(';
                    $html .= $this->getUnitConvertPieceCase($product).' '.__('EA').')</option>';
                }
            } else {
                $html .= '<option value="ea" >'.__('EA').'</option>';
                $html .= '<option value="cs" >'.__('CS').'(';
                $html .= $this->getUnitConvertPieceCase($product).' '.__('EA').')</option>';
            }
        } else {
            $html .= '<option value="ea" >'.__('EA').'</option>';
        }
        $html .= '</select>';

        return $html;
    }

    public function getUnitQty($row)
    {
        $product = $this->productRepository->getById($row->getData('product_id'));

        $html = '<input type="hidden" ';
        $html .= 'name="unit_qty"';
        $html .= 'value="' . $this->getUnitConvertPieceCase($product) . '" ';
        $html .= 'class="input-text admin__control-text unit_qty" />';

        return $html;
    }

    public function getUnitConvertPieceCase($product)
    {
        return (null != $product->getUnitQty())?$product->getUnitQty():1;
    }
}
