<?php

namespace Riki\User\Block\Adminhtml\Password;

class Import extends \Magento\Backend\Block\Widget\Form\Container
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
        $this->_mode = 'Import';
        parent::__construct($context, $data);
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * Initialize cms page edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'upload_id';
        $this->_blockGroup = 'Riki_User';
        $this->_controller = 'adminhtml_password';
        parent::_construct();
        if ($this->_isAllowedAction('Riki_User::save')) {
            $this->buttonList->update('save', 'label', __('Import'));
        } else {
            $this->buttonList->remove('save');
        }
        $this->buttonList->update('save', 'label', __('Import CSV'));
        $this->buttonList->update('save', 'id', 'upload_button');
        $this->buttonList->update('save', 'onclick', 'varienImport.postToFrame();');
        $this->buttonList->update('save', 'data_attribute', '');
    }

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later.
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('riki/user/import/validate', ['_current' => true, 'back' => 'index', 'active_tab' => '{{tab_id}}']);
    }
    /**
     * Retrieve text for header element depending on loaded post.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Import Password');
    }
}