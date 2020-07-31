<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Renderer;

use Riki\CreateProductAttributes\Model\Product\CaseDisplay;

class UnitCase extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * UnitCase constructor.
     *
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Magento\Backend\Block\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Rma\Helper\Data $dataHelper,
        \Magento\Backend\Block\Context $context,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $data);
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
        $content = parent::render($row);
        $orderItem = $this->dataHelper->getRmaItemOrderItem($row);
        if ($orderItem && $orderItem->getData('unit_case') == CaseDisplay::PROFILE_UNIT_CASE) {
            return $content . sprintf('<br><p class="unit-case">%s (%d %s)</p>', CaseDisplay::PROFILE_UNIT_CASE, $orderItem->getData('unit_qty'), CaseDisplay::PROFILE_UNIT_PIECE);
        }

        return parent::render($row);
    }
}