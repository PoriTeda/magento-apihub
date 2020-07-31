<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\FraudCheck\Block\Adminhtml\Rule;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Grid\Extended as ExtendedGrid;
use Magento\Backend\Helper\Data as BackendHelper;
use Mirasvit\FraudCheck\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;

class Grid extends ExtendedGrid
{
    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactory;

    /**
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param Context               $context
     * @param BackendHelper         $backendHelper
     */
    public function __construct(
        RuleCollectionFactory $ruleCollectionFactory,
        Context $context,
        BackendHelper $backendHelper
    ) {
        $this->ruleCollectionFactory = $ruleCollectionFactory;

        parent::__construct($context, $backendHelper);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('feed_rule_grid');
        $this->setDefaultSort('name');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        $collection = $this->ruleCollectionFactory->create();

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

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
                    'url'     => ['base' => '*/*/duplicate'],
                    'field'   => 'id',
                ],
                [
                    'caption' => __('Export'),
                    'url'     => ['base' => '*/*/export'],
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

        return parent::_prepareColumns();
    }

    /**
     * {@inheritdoc}
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', ['id' => $row->getId()]);
    }
}
