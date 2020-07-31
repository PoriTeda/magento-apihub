<?php
namespace Riki\Rma\Plugin\Rma\Block\Adminhtml\Rma\Edit\Tab\Items;

class Grid
{
    /**
     * Extend addColumn()
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid $subject
     * @param \Closure $proceed
     * @param $columnId
     * @param $column
     * @return mixed
     */
    public function aroundAddColumn(\Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid $subject, \Closure $proceed, $columnId, $column)
    {
        $hiddenColumns = [
            'status', 'qty_authorized', 'qty_returned', 'qty_approved',
            'reason', 'condition', 'resolution', 'action'
        ];
        if (in_array($columnId, $hiddenColumns) && $columnId != 'action') {
            unset($column['renderer']);
            $column['header_css_class'] = 'hidden';
            $column['column_css_class'] = 'hidden';
        }

        /** @var \Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Grid $result */
        $result = $proceed($columnId, $column);

        if ($columnId == 'action') {
            $addColumns = [
                'unit_case' => [
                    'header' => __('Unit'),
                    'index' => 'unit_case',
                    'type' => 'text',
                    'sortable' => false,
                    'after' => 'qty_ordered',
                    'renderer' => \Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Renderer\UnitCase::class
                ],
                'return_amount' => [
                    'header' => __('Refund Amount'),
                    'index' => 'return_amount',
                    'sortable' => false,
                    'after' => 'qty_requested',
                    'renderer' => 'Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Renderer\ReturnAmount'
                ],
                'return_amount_adj' => [
                    'header' => __('Refund Amount: Adj'),
                    'index' => 'return_amount_adj',
                    'sortable' => false,
                    'validate_class' => 'validate-number',
                    'editable' => true,
                    'edit_only' => true,
                    'renderer' => 'Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Renderer\ReturnAmountAdjust'
                ],
                'return_amount_final' => [
                    'header' => __('Refund Amount: Final'),
                    'index' => 'return_amount_final',
                    'sortable' => false,
                    'renderer' => 'Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Renderer\ReturnAmountFinal'
                ],
                'return_wrapping_fee' => [
                    'header' => __('Refund Wrapping'),
                    'index' => 'return_wrapping_fee',
                    'type' => 'text',
                    'sortable' => false,
                    'renderer' => 'Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Renderer\ReturnWrapping'
                ],
                'return_wrapping_fee_adj' => [
                    'header' => __('Refund Wrapping: Adj'),
                    'index' => 'return_wrapping_fee_adj',
                    'sortable' => false,
                    'validate_class' => 'validate-number',
                    'editable' => true,
                    'edit_only' => true,
                    'renderer' => 'Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Renderer\ReturnWrappingAdjust'
                ],
                'return_wrapping_fee_final' => [
                    'header' => __('Refund Wrapping: Final'),
                    'index' => 'return_wrapping_fee_final',
                    'sortable' => false,
                    'renderer' => 'Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Renderer\ReturnWrappingFinal'
                ]
            ];
            foreach ($addColumns as $key => $column) {
                if (isset($column['after'])) {
                    $after = $column['after'];
                } else {
                    $after = $subject->getLastColumnId();
                }
                $subject->addColumnAfter($key, $column, $after);
            }
        }


        return $result;
    }

}