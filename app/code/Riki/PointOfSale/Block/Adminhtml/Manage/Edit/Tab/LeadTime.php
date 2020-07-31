<?php

namespace Riki\PointOfSale\Block\Adminhtml\Manage\Edit\Tab;

class LeadTime extends \Magento\Backend\Block\Widget\Grid\Container implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_manage_edit_tab_leadTime';
        $this->_blockGroup = 'Riki_PointOfSale';
        $this->_headerText = __('Lead Times');

        parent::_construct();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Lead Time Settings');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Lead Time Settings');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
