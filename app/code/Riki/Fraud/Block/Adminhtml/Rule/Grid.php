<?php

namespace Riki\Fraud\Block\Adminhtml\Rule;

class Grid extends \Mirasvit\FraudCheck\Block\Adminhtml\Rule\Grid
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn('rule_id', [
            'header' => __('ID'),
            'align'  => 'right',
            'width'  => '50px',
            'index'  => 'rule_id',
        ]);

        $this->addColumn('name', [
            'header' => __('Name'),
            'align'  => 'left',
            'index'  => 'name',
        ]);

        $this->addColumn('conditions', [
            'header'   => __('Conditions'),
            'align'    => 'left',
            'filter'   => false,
            'sortable' => false,
            'renderer' => 'Mirasvit\FraudCheck\Block\Adminhtml\Rule\Grid\Renderer\Conditions',
        ]);

        $this->addColumn('is_active', [
            'header'  => __('Status'),
            'align'   => 'left',
            'width'   => '80px',
            'index'   => 'is_active',
            'type'    => 'options',
            'options' => [
                1 => __('Enabled'),
                0 => __('Disabled'),
            ],
        ]);

        $this->addColumn('action', [
            'header'    => __('Action'),
            'width'     => '100',
            'type'      => 'action',
            'getter'    => 'getId',
            'actions'   => [
                [
                    'caption' => __('Edit'),
                    'url'     => ['base' => '*/*/edit'],
                    'field'   => 'id',
                ],
                [
                    'caption' => __('Duplicate'),
                    'url'     => ['base' => 'riki_fraud/rule/duplicate'],
                    'field'   => 'id',
                ],
                [
                    'caption' => __('Delete'),
                    'url'     => ['base' => '*/*/delete'],
                    'field'   => 'id',
                    'confirm' => __('Are you sure?'),
                ],
            ],
            'filter'    => false,
            'sortable'  => false,
            'is_system' => true,
        ]);

        $this->sortColumnsByOrder();
        return $this;
    }
}
