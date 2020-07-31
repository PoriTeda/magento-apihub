<?php
namespace Riki\Questionnaire\Block\Adminhtml\Questions;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

/**
 * Class Edit
 * @package Riki\Questionnaire\Block\Adminhtml\Questions
 */
class Edit extends Container
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
        Context $context,
        Registry $registry,
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
        $this->_objectId = 'enquete_id';
        $this->_blockGroup = 'Riki_Questionnaire';
        $this->_controller = 'adminhtml_questions';

        parent::_construct();

        if ($this->_isAllowedAction('Riki_Questionnaire::save')) {
            $this->buttonList->update('save', 'label', __('Save Questionnaire'));
            $this->buttonList->add(
                'saveandcontinue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                ],
                -100
            );
        } else {
            $this->buttonList->remove('save');
        }

        if ($this->_isAllowedAction('Riki_Questionnaire::delete')) {
            $this->buttonList->update('delete', 'label', __('Delete Questionnaire'));
        } else {
            $this->buttonList->remove('delete');
        }
        $question = $this->_coreRegistry->registry('current_questionnaire');

        if ($question->getId()) {
            $this->buttonList->remove('reset');
        } 

    }

    /**
     * Retrieve text for header element depending on loaded post
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $question = $this->_coreRegistry->registry('current_questionnaire');
        if ($question->getId()) {
            return __("Edit  '%1'", $this->escapeHtml($question->getName()));
        } else {
            return __('New Questionnaire');
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
        return $this->getUrl('questionnaire/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '{{tab_id}}']);
    }

    /**
     * Form scripts
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
   })
</script>
        ";
    }

}