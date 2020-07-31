<?php

namespace Riki\PointOfSale\Block\Adminhtml\Manage\Edit\Tab;

class Delitypesetting extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    protected $_systemStore = null;
    protected $_deliHelper = null;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Riki\PointOfSale\Helper\Data $delitypeHelper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_systemStore = $systemStore;
        $this->_deliHelper = $delitypeHelper;
    }


    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('pointofsale');
        $form = $this->_formFactory->create();
        $fieldset = $form->addFieldset('delitypesetting', ['legend' => __('Delivery Type Settings')]);

        $storeView = $this->_systemStore->getStoreValuesForForm(false, true);
        array_shift($storeView);
        array_unshift($storeView, ['value' => 0, 'label' => __('No Delivery Type Settings')]);

        $fieldset->addField(
            'deliverytype_enable_list',
            'multiselect',
            [
                'label' => __('Select Delivery Type'),
                'name' => 'deliverytype_enable_list',
                'values' => $this->_deliHelper->getDelitypeArray(),
            ]
        );
        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getTabLabel()
    {
        return __('Delivery Type Selection');
    }

    public function getTabTitle()
    {
        return __('Delivery Type  Settings');
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
