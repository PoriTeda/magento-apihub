<?php

namespace Wyomind\PointOfSale\Block\Adminhtml\Manage\Edit\Tab;

class Storeviews extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    protected $_systemStore = null;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_systemStore = $systemStore;
    }

  
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('pointofsale');

        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset('storeviews', ['legend' => __('Store Views Selection')]);

        $storeView = $this->_systemStore->getStoreValuesForForm(false, true);
        array_shift($storeView);
        array_unshift($storeView, ['value' => 0, 'label' => __('No Store View')]);

        $fieldset->addField(
            'store_id',
            'multiselect',
            [
            'name' => 'store_id[]',
            'label' => __('Store View'),
            'title' => __('Store View'),
            'class' => 'validate-select',
            'required' => true,
            'values' => $storeView,
            ]
        );

        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getTabLabel()
    {
        return __('Store Views Selection');
    }

    public function getTabTitle()
    {
        return __('Store Views Selection');
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
