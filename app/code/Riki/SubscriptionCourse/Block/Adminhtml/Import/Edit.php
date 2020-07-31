<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Import;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Construct Import
     */
    public function _construct()
    {
        $this->_objectId   = 'id';
        $this->_blockGroup = 'Riki_SubscriptionCourse';
        $this->_controller = 'adminhtml_import';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Check Data'));
        $this->buttonList->update('save', 'id', 'upload_button');
        $this->buttonList->update('save', 'onclick', 'rikiSubCourseCsvUpload.postToFrame();');
        $this->buttonList->update('save', 'data_attribute', '');
    }
}
