<?php
namespace Riki\Questionnaire\Block\Adminhtml\Answers;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

/**
 * Class View
 * @package Riki\Questionnaire\Block\Adminhtml\Answers
 */
class View extends Container
{
    /**
     * @var string
     */
    protected $_mode = 'view';
    
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
        $this->_objectId = 'answer_id';
        $this->_blockGroup = 'Riki_Questionnaire';
        $this->_controller = 'adminhtml_answers';

        parent::_construct();

        $this->buttonList->remove('save');
        $this->buttonList->remove('reset');

        if ($this->_isAllowedAction('Riki_Questionnaire::answersdelete')) {
            $this->buttonList->update('delete', 'label', __('Delete Answer'));
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
        $answers = $this->_coreRegistry->registry('current_answers');
        if ($answers->getId()) {
            return __("View Detail  '%1'", $this->escapeHtml($answers->getId()));
        } else {
            return __('Answers');
        }
    }
}