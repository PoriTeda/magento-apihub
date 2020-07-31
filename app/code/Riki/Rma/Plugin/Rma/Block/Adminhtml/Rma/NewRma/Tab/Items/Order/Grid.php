<?php
namespace Riki\Rma\Plugin\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Order;

use Riki\CreateProductAttributes\Model\Product\CaseDisplay;

class Grid
{
    /**
     * Add additional columns
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Order\Grid $subject
     * @param \Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Order\Grid $result
     *
     * @return \Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Order\Grid
     */
    public function afterAddColumn(
        \Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Order\Grid $subject,
        \Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Order\Grid $result
    ){
        if ($result->getLastColumnId() == 'available_qty') {
            $result->addColumn('unit_case', [
                'header' => __('Unit'),
                'type' => 'default',
                'header_css_class' => 'hidden',
                'column_css_class' => 'hidden',
                'filter' => false,
                'sortable' => false,
                'default' => CaseDisplay::PROFILE_UNIT_PIECE // https://rikibusiness.atlassian.net/wiki/display/MS/10.3+Return+control?focusedCommentId=60751922#comment-60751922 will be completed solution later
            ]);
            $result->addColumn('unit_case_ordered', [
                'header' => __('Unit case ordered'),
                'type' => 'text',
                'index' => 'unit_case',
                'header_css_class' => 'hidden',
                'column_css_class' => 'hidden',
                'filter' => false,
                'sortable' => false,
            ]);
            $result->addColumn('unit_qty_ordered', [
                'header' => __('Unit qty'),
                'type' => 'text',
                'index' => 'unit_qty',
                'header_css_class' => 'hidden',
                'column_css_class' => 'hidden',
                'filter' => false,
                'sortable' => false
            ]);
        }

        return $result;
    }
}