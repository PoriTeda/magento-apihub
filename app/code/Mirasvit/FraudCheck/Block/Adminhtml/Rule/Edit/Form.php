<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.38
 * @copyright Copyright (C) 2019 Mirasvit (https://mirasvit.com/)
 */


namespace Mirasvit\FraudCheck\Block\Adminhtml\Rule\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form as WidgetForm;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;
use Magento\Rule\Block\Conditions as RuleConditions;

class Form extends WidgetForm
{
    /**
     * @var FormFactory
     */
    protected $formFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Fieldset
     */
    protected $fieldset;

    /**
     * @var RuleConditions
     */
    protected $ruleConditions;

    /**
     * @param FormFactory    $formFactory
     * @param Registry       $registry
     * @param Fieldset       $fieldset
     * @param RuleConditions $ruleConditions
     * @param Context        $context
     */
    public function __construct(
        FormFactory $formFactory,
        Registry $registry,
        Fieldset $fieldset,
        RuleConditions $ruleConditions,
        Context $context
    ) {
        $this->formFactory = $formFactory;
        $this->fieldset = $fieldset;
        $this->registry = $registry;
        $this->ruleConditions = $ruleConditions;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareForm()
    {
        /** @var \Mirasvit\FraudCheck\Model\Rule $model */
        $model = $this->registry->registry('current_model');

        $form = $this->formFactory->create()->setData([
            'id'      => 'edit_form',
            'action'  => $this->getData('action'),
            'method'  => 'post',
            'enctype' => 'multipart/form-data',
        ]);

        $form->setUseContainer(true);
        $this->setForm($form);

        $form->setFieldNameSuffix('data');
        $this->setForm($form);

        $general = $form->addFieldset('general_fieldset', ['legend' => __('General Information')]);

        if ($model->getId()) {
            $general->addField('rule_id', 'hidden', [
                'name'  => 'rule_id',
                'value' => $model->getId(),
            ]);
        }

        $general->addField('name', 'text', [
            'label'    => __('Name'),
            'required' => true,
            'name'     => 'name',
            'value'    => $model->getName(),
        ]);

        $general->addField('is_active', 'select', [
            'label'    => __('Is Active'),
            'required' => true,
            'name'     => 'is_active',
            'value'    => $model->getIsActive(),
            'values'   => [0 => __('No'), 1 => __('Yes')],
        ]);

        $general->addField('status', 'select', [
            'label'    => __('Set status to'),
            'required' => true,
            'name'     => 'status',
            'value'    => $model->getStatus(),
            'values'   => [
                'accept' => __('Accept'),
                'review' => __('Review'),
                'reject' => __('Reject')
            ],
        ]);

        $renderer = $this->fieldset
            ->setTemplate('Magento_CatalogRule::promo/fieldset.phtml')
            ->setNewChildUrl($this->getUrl(
                '*/rule/newConditionHtml/form/rule_conditions_fieldset',
                []
            ));

        $fieldset = $form->addFieldset(
            'conditions_fieldset',
            ['legend' => __('Conditions')]
        )->setRenderer($renderer);

        $fieldset->addField('conditions', 'text', [
            'name'     => 'conditions',
            'label'    => __('Rules'),
            'title'    => __('Rules'),
            'required' => true,
        ])->setRule($model)
            ->setRenderer($this->ruleConditions);

        $form->setValues($model->getData());

        return parent::_prepareForm();
    }
}
