<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Block\Adminhtml\Customersegment\Grid;

/**
 * Customer Segment grid
 *
 * @api
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @since 100.0.2
 */
class Chooser extends \Magento\CustomerSegment\Block\Adminhtml\Customersegment\Grid
{
    /**
     * Intialize grid
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        if ($this->getRequest()->getParam('current_grid_id')) {
            $this->setId($this->getRequest()->getParam('current_grid_id'));
        } else {
            $this->setId('customersegment_grid_chooser_' . $this->getId());
        }

        $this->setDefaultSort('name');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);

        $form = $this->getRequest()->getParam('form');
        if ($form) {
            $this->setRowClickCallback("{$form}.chooserGridRowClick.bind({$form})");
            $this->setCheckboxCheckCallback("{$form}.chooserGridCheckboxCheck.bind({$form})");
            $this->setRowInitCallback("{$form}.chooserGridRowInit.bind({$form})");
        }
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
    }

    /**
     * Row click javascript callback getter
     *
     * @return string
     */
    public function getRowClickCallback()
    {
        return $this->_getData('row_click_callback');
    }

    /**
     * Prepare columns for grid
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_segments',
            [
                'header_css_class' => 'a-center',
                'type' => 'checkbox',
                'name' => 'in_segments',
                'values' => $this->_getSelectedSegments(),
                'align' => 'center',
                'index' => 'segment_id',
                'use_index' => true
            ]
        );
        return parent::_prepareColumns();
    }

    /**
     * Get Selected ids param from request
     *
     * @return array
     */
    protected function _getSelectedSegments()
    {
        $segments = $this->getRequest()->getPost('selected', []);
        return $segments;
    }

    /**
     * Grid URL getter for ajax mode
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('customersegment/index/chooserGrid', ['_current' => true]);
    }
}
