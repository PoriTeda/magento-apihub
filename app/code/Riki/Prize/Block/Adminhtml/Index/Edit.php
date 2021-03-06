<?php
namespace Riki\Prize\Block\Adminhtml\Index;

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
        $this->_objectId = 'prize_id';
        $this->_blockGroup = 'Riki_Prize';
        $this->_controller = 'adminhtml_index';

        parent::_construct();

        if ($this->_isAllowedAction('Riki_Prize::prize')) {
            $this->buttonList->update('save', 'label', __('Save Prize'));

            /* remove event onclick default of magento */
            $this->buttonList->update('save', 'data_attribute', '');
            /* add custom function submitForm to validate and prevent double click save action */
            $this->buttonList->update('save', 'onclick', 'submitForm(this, \'edit_form\')');

            $this->buttonList->add(
                'saveandcontinue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'onclick' => 'submitForm(this, \'edit_form\', \'saveandcontinue\')',
                ],
                -100
            );
        } else {
            $this->buttonList->remove('save');
        }

        if ($this->_isAllowedAction('Riki_Prize::prize')) {
            $this->buttonList->update('delete', 'label', __('Delete Prize'));
        } else {
            $this->buttonList->remove('delete');
        }
    }

    /**
     * Retrieve text for header element depending on loaded post
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $course = $this->_coreRegistry->registry('prize_item');
        if ($course->getId()) {
            return __("Edit  '%1'", $this->escapeHtml($course->getName()));
        } else {
            return __('New Prize');
        }
    }

    /**
     * Getter of url for "Save and Continue" button
     * tab_id will be replaced by desired by JS later
     *
     * @return string
     */
    protected function _getSaveAndContinueUrl()
    {
        return $this->getUrl('prize/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '{{tab_id}}']);
    }

    /**
     * @return string
     */
    public function getFormScripts()
    {
        return " 
        <script>
        require([
            'jquery',
            'mage/backend/form',
            'mage/backend/validation'
        ], function($){
            submitForm = function (actionButton, formElement, action = false) {
                // Disable button save prize to prevent double form submission
                $(actionButton).attr('disabled', true);
                
                // Validate before submit form
                var isValid = $('#' + formElement).mage('validation').valid();
                if (isValid) {
                    $('body').trigger('processStart');
                    if (action && action == 'saveandcontinue') {
                        $('#' + formElement).append(\"<input type='hidden' name='back' value='1' />\");
                    }
                    return $('#' + formElement).submit();
                }
        
                // Enable again button save prize if validation is false
                $(actionButton).attr('disabled', false);
                return false;
            };
        });
        </script>";
    }
}
