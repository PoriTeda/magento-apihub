<?php
namespace Riki\AdvancedInventory\Block\Adminhtml\ReAssignation;

class Edit extends  \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Construct Import
     */
    public function _construct()
    {
        $this->_objectId   = 'id';
        $this->_blockGroup = 'Riki_AdvancedInventory';
        $this->_controller = 'adminhtml_reAssignation';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Check Data'));
        $this->buttonList->update('save', 'id', 'upload_button');
        $this->buttonList->update('save', 'onclick', 'rikiCsvReAssignationUpload.postToFrame();');
        $this->buttonList->update('save', 'data_attribute', '');
    }

}