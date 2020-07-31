<?php

namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab;

class DisengagementSetting extends \Magento\Backend\Block\Widget\Form\Generic
    implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @return \Magento\Backend\Block\Widget\Form\Generic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('subscription_course');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('disengagement_setting');
        $fieldset = $form->addFieldset(
            'disengagement_setting_fieldset',
            ['legend' => __('Disengagement Setting')]
        );

        $fieldset->addField(
            'is_allow_cancel_from_frontend',
            'select',
            [
                'name' => 'is_allow_cancel_from_frontend',
                'id' => 'is_allow_cancel_from_frontend',
                'label' => __('Is allow cancel from frontend'),
                'title' => __('Is allow cancel from frontend'),
                'values' => $model->getYesNo(),
                'required' => true,
                'onchange' => 'disableRequireMinimumOrderTimes()'
            ]
        );

        $fieldset->addField(
            'minimum_order_times',
            'text',
            [
                'name' => 'minimum_order_times',
                'id' => 'minimum_order_times',
                'label' => __('Minimum order times'),
                'title' => __('Minimum order times'),
                'class' => 'validate-number validate-greater-than-zero',
                'required' => $model->getData('is_allow_cancel_from_frontend'),
            ]
        );

        $fieldset->addField(
            'sales_count',
            'text',
            [
                'name' => 'sales_count',
                'id' => 'sales_count',
                'label' => __('Sales qty count'),
                'title' => __('Sales qty count'),
                'class' => 'validate-number',
                'required' => false,
            ]
        );

        $fieldset->addField(
            'sales_value_count',
            'text',
            [
                'name' => 'sales_value_count',
                'id' => 'sales_value_count',
                'label' => __('Sales value count'),
                'title' => __('Sales value count'),
                'class' => 'validate-number',
                'required' => false,
            ]
        );

        $form->setValues($model->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * @return string
     */
    public function getOptionsBoxHtml()
    {
        return $this->getChildHtml('options_box');
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Disengagement Setting');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Disengagement Setting');
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
