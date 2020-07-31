<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Block\Adminhtml\Journal;

/**
 * Prepar the prodiles grid
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    protected $_modelJournal;
    protected $_journalCollection;
    protected $_coreHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Wyomind\AdvancedInventory\Model\Journal $modelJournal,
        \Wyomind\AdvancedInventory\Model\ResourceModel\Journal\Collection $journalCollection,
        \Wyomind\Core\Helper\Data $coreHelper,
        array $data = []
    ) {
        $this->_modelJournal = $modelJournal;
        $this->_journalCollection = $journalCollection;
        $this->_coreHelper = $coreHelper;

        parent::__construct($context, $backendHelper, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->setId('AdvancedInventoryJournal');
        $this->setDefaultSort('datetime');
        $this->setDefaultDir('DESC');
    }

    protected function _prepareCollection()
    {
        $collection = $this->_journalCollection;
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn(
            'datetime',
            [
            'header' => __('Date/Time'),
            'width' => '150px',
            'type' => 'datetime',
            'index' => 'datetime',
            ]
        );

        $this->addColumn(
            'user',
            [
            'header' => __('User'),
            'width' => '100px',
            'type' => 'options',
            'options' => $this->_modelJournal->getUsers(),
            'index' => 'user',
            ]
        );
        
        $this->addColumn(
            'context',
            [
            'header' => __('Context'),
            'width' => '100px',
            'type' => 'options',
            'options' => $this->_modelJournal->getContexts(),
            'index' => 'context',
            ]
        );
        
        $this->addColumn(
            'action',
            [
            'header' => __('Action'),
            'width' => '150px',
            'type' => 'options',
            'options' => $this->_modelJournal->getActions(),
            'index' => 'action',
            ]
        );
        $this->addColumn(
            'reference',
            [
            'header' => __('Reference'),
            'width' => '150px',
            'type' => 'text',
            'index' => 'reference',
            'renderer' => \Wyomind\AdvancedInventory\Block\Adminhtml\Journal\Renderer\Reference::class,
            ]
        );
        $this->addColumn(
            'details',
            [
            'header' => __('Details'),
            'width' => '',
            'type' => 'text',
            'index' => 'details',
            "filter" => false,
            "sortable" => false
            ]
        );

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return false;
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        $this->getMassactionBlock()->addItem(
            'delete',
            [
            'label' => __('Purge'),
            'value' => true,
            'url' => $this->getUrl('*/*/purge')
            ]
        );


        return $this;
    }
}
