<?php

namespace Riki\CatalogRule\Block\Adminhtml\Wbs\Edit\Tab;

class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Prepare form
     * 
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_wbs_conversion_form');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('wbs_');

        $fieldSet = $form->addFieldset('base_fieldset', ['legend' => __('Basic Information')]);

        if ($model->getId()) {
            $fieldSet->addField('entity_id', 'hidden', ['name' => 'entity_id']);
        }
        
        $fieldSet->addField(
            'old_wbs',
            'text',
            [
                'name' => 'old_wbs',
                'id' => 'old_wbs',
                'label' => __('Old Wbs Code'),
                'title' => __('Old Wbs Code'),
                'class' => 'required-entry validate-wbs-code',
                'unique' => true,
                'required' => true
            ]
        );

        $fieldSet->addField(
            'new_wbs',
            'text',
            [
                'name' => 'new_wbs',
                'id' => 'new_wbs',
                'label' => __('New Wbs Code'),
                'title' => __('New Wbs Code'),
                'class' => 'required-entry validate-wbs-code',
                'unique' => true,
                'required' => true
            ]
        );

        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $timeFormat = $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT);
        $fieldSet->addField(
            'from_date',
            'date',
            [
                'name' => 'from_date',
                'id' => 'from_date',
                'label' => __('Conversion Start Date'),
                'title' => __('Conversion Start Date'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                'date_format' => $dateFormat,
                'class' => 'validate-date required-entry validate-date-range date-range-from_date-from',
                'required' => true
            ]
        );

        $fieldSet->addField(
            'from_time',
            'time',
            [
                'name' => 'from_time',
                'label' => __('Start Time'),
                'title' => __('Start Time'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT,
                'time_format' => $timeFormat
            ]
        );

        $fieldSet->addField(
            'to_date',
            'date',
            [
                'name' => 'to_date',
                'id' => 'to_date',
                'label' => __('Conversion End Date'),
                'title' => __('Conversion End Date'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT,
                'date_format' => $dateFormat,
                'class' => 'validate-date required-entry validate-date-range date-range-to_date-to',
                'required' => true
            ]
        );

        $fieldSet->addField(
            'to_time',
            'time',
            [
                'name' => 'to_time',
                'label' => __('End Time'),
                'title' => __('End Time'),
                'input_format' => \Magento\Framework\Stdlib\DateTime::DATETIME_INTERNAL_FORMAT,
                'time_format' => $timeFormat
            ]
        );

        $fieldSet->addField(
            'is_active',
            'select',
            [
                'label' => __('Status'),
                'title' => __('Status'),
                'required' => true,
                'name'     => 'is_active',
                'id'     => 'is_active',
                'value'    => $model->getIsActive(),
                'values'   => [1 => __('Active'), 0 => __('Inactive')],
            ]
        );

        $form->setValues($model->getData());

        $form->setFieldNameSuffix('wbs');

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
        return __('Basic information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Basic information');
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
    
}