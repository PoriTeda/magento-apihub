<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course;

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
        $this->_objectId = 'course_id';
        $this->_blockGroup = 'Riki_SubscriptionCourse';
        $this->_controller = 'adminhtml_course';

        parent::_construct();

        if ($this->_isAllowedAction('Riki_SubscriptionCourse::save')) {
            $this->buttonList->update('save', 'label', __('Save Subscription Course'));

            /* remove event onclick default of magento */
            $this->buttonList->update('save', 'data_attribute', '');
            /* add custom function submitForm to validate Order total minimum amount option */
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

        if ($this->_isAllowedAction('Riki_SubscriptionCourse::delete')) {
            $this->buttonList->update('delete', 'label', __('Delete Subscription Course'));
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
        $course = $this->_coreRegistry->registry('subscription_course');
        if ($course->getId()) {
            return __("Edit  '%1'", $this->escapeHtml($course->getName()));
        } else {
            return __('New Subscription Course');
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
        return $this->getUrl('subscription/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '{{tab_id}}']);
    }
}
