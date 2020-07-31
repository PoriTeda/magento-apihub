<?php

namespace Riki\SerialCode\Block\Adminhtml\SerialCode\Edit\Tab;

/**
 * Adminhtml serial code edit form
 */
class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('serial_code');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('serial_code_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information'), 'class' => 'fieldset-wide']
        );

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
            $fieldset->addField(
                'serial_code',
                'text',
                ['name' => 'serial_code', 'label' => __('Serial Code'), 'title' => __('Serial Code'), 'required' => true]
            );
        } else {
            $fieldset->addField(
                'number_of_generate',
                'text',
                [
                    'name' => 'number_of_generate', 'label' => __('Number of serial code to generate'),
                    'title' => __('Number of serial code to generate'), 'required' => true,
                    'class' => 'validate-greater-than-zero number',
                ]
            );
        }

        $fieldset->addField(
            'issued_point',
            'text',
            [
                'name' => 'issued_point',
                'label' => __('Issued point'),
                'title' => __('Issued point'),
                'required' => true,
                'class' => 'validate-greater-than-zero number'
            ]
        );
        $fieldset->addField(
            'point_expiration_period',
            'text',
            [
                'name' => 'point_expiration_period',
                'label' => __('Point expiration period (in days)'),
                'title' => __('Point expiration period'),
                'required' => false,
                'class' => 'validate-not-negative-number validate-number'
            ]
        );
        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $timeFormat = $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT);
        $fieldset->addField(
            'activation_date',
            'date',
            [
                'name' => 'activation_date',
                'label' => __('Activation date'),
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'class' => 'validate-date validate-date-range date-range-expire-from',
                'required' => true,
            ]
        );
        $fieldset->addField(
            'expiration_date',
            'date',
            [
                'name' => 'expiration_date',
                'label' => __('Expiration date'),
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'class' => 'validate-date validate-date-range date-range-expire-to'
            ]
        );
        $fieldset->addField(
            'wbs',
            'text',
            [
                'name' => 'wbs', 'label' => __('WBS'), 'title' => __('WBS'),
                'required' => true,
                'class' => 'validate-wbs-code'
            ]
        );

        $fieldset->addField(
            'account_code',
            'text',
            ['name' => 'account_code', 'label' => __('Account code'), 'title' => __('Account code'), 'required' => true]
        );
        $fieldset->addField(
            'campaign_id',
            'text',
            [
                'name' => 'campaign_id',
                'label' => __('Campaign ID'),
                'title' => __('Campaign ID'),
                'required' => false,
                'class' => ''
            ]
        );
        $fieldset->addField(
            'campaign_limit',
            'text',
            [
                'name' => 'campaign_limit',
                'label' => __('Limit per campaign ID'),
                'title' => __('Limit per campaign ID'),
                'required' => false,
                'class' => 'validate-not-negative-number number'
            ]
        );
        if ($model->getId()) {
            $fieldset->addField(
                'status',
                'select',
                [
                    'label' => __('Status'),
                    'title' => __('Status'),
                    'name' => 'status',
                    'required' => true,
                    'options' => ['1' => __('Not used'), /*'2' => __('Used'),*/ '3' => __('Cancelled')]
                ]
            );
        }

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
        return __('General Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('General Information');
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
        return true;
    }
}
