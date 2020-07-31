<?php
namespace Riki\Questionnaire\Block\Adminhtml\Questions;
use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;

/**
 * Class Import
 * @package Riki\Questionnaire\Block\Adminhtml\Questions
 */
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
        Context $context,
        Registry $registry,
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
        $this->_objectId = 'enquete_id';
        $this->_blockGroup = 'Riki_Questionnaire';
        $this->_controller = 'adminhtml_Questions';
        parent::_construct();
        if ($this->_isAllowedAction('Riki_Questionnaire::save')) {
            $this->buttonList->update('save', 'label', __('Import'));
        } else {
            $this->buttonList->remove('save');
        }
    }

    /**
     * Retrieve text for header element depending on loaded post
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __('Import Questionnaire');
    }
}