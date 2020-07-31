<?php
namespace Riki\Rma\Plugin\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items;

class Grid
{
    /**
     * Extend addColumn()
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Grid $subject
     * @param \Closure $proceed
     * @param $columnId
     * @param $column
     *
     * @return mixed
     */
    public function aroundAddColumn(\Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Grid $subject, \Closure $proceed, $columnId, $column)
    {
        $lastColumnId = $subject->getLastColumnId();

        /** @var \Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Grid $result */
        $result = $proceed($columnId, $column);

        if ($columnId == 'action') {
            $subject->addColumnAfter('unit_case', [
                'header' => __('Unit'),
                'index' => 'unit_case',
                'type' => 'text',
                'sortable' => false,
                'renderer' => \Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Renderer\UnitCase::class
            ], 'qty_ordered');
        }

        $removeColumns = ['reason', 'condition', 'resolution'];
        if (in_array($lastColumnId, $removeColumns)
            && $result->getLastColumnId() != $lastColumnId
        ) {
            $result->removeColumn($lastColumnId);
        }

        return $result;
    }
}
