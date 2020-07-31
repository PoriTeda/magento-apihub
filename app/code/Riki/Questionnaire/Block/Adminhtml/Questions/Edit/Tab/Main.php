<?php

namespace Riki\Questionnaire\Block\Adminhtml\Questions\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Form;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Stdlib\DateTime;

/**
 * Class Main
 * @package Riki\Questionnaire\Block\Adminhtml\Questions\Edit\Tab
 */
class Main extends Generic implements TabInterface
{
    protected $_systemStore;

    /**
     * Main constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        /** @var \Riki\Questionnaire\Model\Questionnaire $model */
        $model = $this->_coreRegistry->registry('current_questionnaire');

        $isElementDisabled = false;

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();

        $form->setHtmlIdPrefix('questionnaire_');

        $fieldSet = $form->addFieldset('base_fieldset', ['legend' => __('Questionnaire Information')]);

        if ($model->getId()) {
            $fieldSet->addField('enquete_id', 'hidden', ['name' => 'enquete_id']);
        }

        $fieldSet->addField(
            'enquete_type',
            'select',
            [
                'label' => __('Questionnaire Type'),
                'title' => __('Questionnaire Type'),
                'name' => 'enquete_type',
                'required' => true,
                'values' => $model->getQuestionnaireType(),
                'onchange' => 'toggleRelatedFields()',
                'disabled' => $model->getData('enquete_type') != null ? true : false
            ]
        );

        $fieldSet->addField(
            'code',
            'text',
            [
                'name' => 'code',
                'id' => 'code',
                'label' => __('Enquete Code'),
                'title' => __('Enquete Code'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );

        $fieldSet->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'id' => 'name',
                'label' => __('Enquete Name'),
                'title' => __('Enquete Name'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );
        $fieldSet->addField(
            'start_date',
            'date',
            [
                'name' => 'start_date',
                'label' => __('Enquete start date'),
                'title' => __('Enquete start date'),
                'input_format' => DateTime::DATE_INTERNAL_FORMAT,
                'date_format' => 'MM/dd/yyyy',
                'required' => true,
                'class' => 'validate-date validate-date-range date-range-questionnaire-from'
            ]
        );

        $fieldSet->addField(
            'end_date',
            'date',
            [
                'name' => 'end_date',
                'label' => __('Enquete end date'),
                'title' => __('Enquete end date'),
                'input_format' => DateTime::DATE_INTERNAL_FORMAT,
                'date_format' => 'MM/dd/yyyy',
                'required' => true,
                'class' => 'validate-date validate-date-range date-range-questionnaire-to',
            ]
        );

        $fieldSet->addField(
            'priority',
            'text',
            [
                'name' => 'priority',
                'id' => 'priority',
                'label' => __('Priority'),
                'title' => __('Priority'),
                'class' => 'validate-number validate-greater-than-zero validate-digits',
                'required' => false,
            ]
        );

        $fieldSet->addField(
            'linked_product_sku',
            'text',
            [
                'name' => 'linked_product_sku',
                'id' => 'linked_product_sku',
                'label' => __('Product SKU or Subscription code'),
                'title' => __('Product SKU or Subscription code'),
                'required' => true,
                'class' => 'required-entry',
                'note' => __('Input product SKU or Subscription code')
            ]
        );

        $fieldSet->addField(
            'visible_on_checkout',
            'select',
            [
                'label' => __('Visible on checkout'),
                'title' => __('Visible on checkout'),
                'name' => 'visible_on_checkout',
                'values' => $model->getYesNo()
            ]
        );

        $fieldSet->addField(
            'visible_on_order_success_page',
            'select',
            [
                'label' => __('Visible on order success page'),
                'title' => __('Visible on order success page'),
                'name' => 'visible_on_order_success_page',
                'values' => $model->getYesNo()
            ]
        );

        $fieldSet->addField(
            'is_available_backend_only',
            'select',
            [
                'label' => __('Available backend only'),
                'title' => __('Available backend only'),
                'name' => 'is_available_backend_only',
                'values' => $model->getYesNo()
            ]
        );


        $fieldSet->addField(
            'is_enabled',
            'select',
            [
                'label' => __('Active'),
                'title' => __('Active'),
                'name' => 'is_enabled',
                'required' => true,
                'values' => $model->getAvailableStatuses()
            ]
        );
        if (!$model->getId()) {
            $model->setData('is_enabled', 1);
        }


        $form->setValues($model->getData());
        $form->setFieldNameSuffix('questionnaire');
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
        return __('Questionnaire information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Questionnaire information');
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