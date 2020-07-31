<?php

namespace Wyomind\PointOfSale\Block\Adminhtml\Manage\Edit\Tab;

class Customergroups extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    protected $_groupModel = null;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Customer\Model\Group $groupModel,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_groupModel = $groupModel;
    }
    
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('pointofsale');

        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset('customergroup', ['legend' => __('Customer Group Selection')]);

        $_customerGroup = [];
        $allGroups = $this->_groupModel->getCollection()->toOptionHash();

        foreach ($allGroups as $key => $allGroup) {
            $_customerGroup[$key] = ['value' => $key, 'label' => $allGroup];
        }
        array_unshift($_customerGroup, ['value' => "-1", 'label' => __('No Customer Group')]);


        $fieldset->addField(
            'customer_group',
            'multiselect',
            [
            'name' => 'customer_group[]',
            'label' => __('Customer Group'),
            'title' => __('Customer Group'),
            'class' => 'validate-select',
            'required' => true,
            'values' => $_customerGroup,
            ]
        );

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getTabLabel()
    {
        return __('Customer Group Selection');
    }

    public function getTabTitle()
    {
        return __('Customer Group Selection');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }
}
