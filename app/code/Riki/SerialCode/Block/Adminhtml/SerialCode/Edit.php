<?php

namespace Riki\SerialCode\Block\Adminhtml\SerialCode;

/**
 * Serial code edit form container
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Riki_SerialCode';
        $this->_controller = 'adminhtml_serialCode';

        parent::_construct();

        if($this->_authorization->isAllowed('Riki_SerialCode::serial_code_action_save')){
            $this->buttonList->update('save', 'label', __('Save code'));
        }else{
            $this->buttonList->remove('save');
        }

        if($this->_authorization->isAllowed('Riki_SerialCode::serial_code_action_delete')){
            $this->buttonList->update('delete', 'label', __('Delete code'));
        }else{
            $this->buttonList->remove('delete');
        }

    }

    /**
     * Get edit form container header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __("Edit serial code '%1'", $this->escapeHtml($this->_coreRegistry->registry('serial_code')->getSerialCode()));
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return true;
    }
}
