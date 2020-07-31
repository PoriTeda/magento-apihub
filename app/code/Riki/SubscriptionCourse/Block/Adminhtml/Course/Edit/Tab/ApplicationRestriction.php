<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab;

use Magento\Backend\Block\Widget\Tab\TabInterface;

class ApplicationRestriction extends \Magento\Backend\Block\Widget\Form\Generic implements TabInterface
{
    /**
     * Prepare form
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Riki\SubscriptionCourse\Model\Course $model */
        $model = $this->_coreRegistry->registry('subscription_course');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('cou_');
        $fieldset = $form->addFieldset(
            'application_limit_fieldset',
            ['legend' => __('Application Restriction')]
        );
        $fieldset->addField(
            'application_limit',
            'text',
            [
                'name' => 'application_limit',
                'id' => 'application_limit',
                'label' => __('Application Limit'),
                'title' => __('Application Limit'),
                'class' => 'validate-number',
                'required' => false,
            ]
        );
        $fieldset->addField(
            'restrict_active_course',
            'select',
            [
                'name' => 'restrict_active_course',
                'id' => 'restrict_active_course',
                'label' => __('Restrict active course'),
                'title' => __('Restrict active course'),
                'required' => false,
                'values' => $model->getYesNo()
            ]
        );
        $fieldset->addField(
            'restrict_inactive_course',
            'select',
            [
                'name' => 'restrict_inactive_course',
                'id' => 'restrict_inactive_course',
                'label' => __('Restrict inactive course'),
                'title' => __('Restrict inactive course'),
                'required' => false,
                'values' => $model->getYesNo()
            ]
        );

        $fieldset->addField(
            'restrict_exclude_course',
            'text',
            [
                'name' => 'restrict_exclude_course',
                'id' => 'restrict_exclude_course',
                'label' => __('Restrict Exclude Courses'),
                'title' => __('Restrict Exclude Courses'),
                'required' => false,
                'note' => __('Comma-separated (Example: RT*,NS*,MS000001)')
            ]
        );

        $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Application Restriction');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Application Restriction');
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
     * @return mixed
     */
    public function getCurrentCourse()
    {
        return $this->_coreRegistry->registry('subscription_course');
    }
}
