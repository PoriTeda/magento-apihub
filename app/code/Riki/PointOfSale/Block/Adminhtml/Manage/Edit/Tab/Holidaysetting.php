<?php

namespace Riki\PointOfSale\Block\Adminhtml\Manage\Edit\Tab;

class Holidaysetting extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
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

        $fieldset = $form->addFieldset('holidaysetting', ['legend' => __('Holidays Settings')]);

        $storeView = $this->_systemStore->getStoreValuesForForm(false, true);
        array_shift($storeView);
        array_unshift($storeView, ['value' => 0, 'label' => __('No Holidays Settings')]);

        $fieldset->addField(
            'holyday_setting_saturday_enable',
            'select',
            [
                'label' => __('Holidays on Saturday'),
                'name' => 'holyday_setting_saturday_enable',
                "options" => [
                    1 => __('Yes'),
                    0 => __('No'),
                ],
                "selected" => 0
            ]
        );

        $fieldset->addField(
            'holyday_setting_sundays_enable',
            'select',
            [
                'label' => __('Holidays on Sunday'),
                'name' => 'holyday_setting_sundays_enable',
                "options" => [
                    1 => __('Yes'),
                    0 => __('No'),
                ],
                "selected" => 0
            ]
        );
        $fieldset->addField(
            'specific_holidays',
            'textarea',
            [
                'label' => __(' Specific holidays (non-working days)'),
                'name' => 'specific_holidays',
                'note'      => __('Date in format of yyyy-MM-DD - date separated by semi colon ";"')
            ]
        );
        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getTabLabel()
    {
        return __('Holidays Settings Selection');
    }

    public function getTabTitle()
    {
        return __('Holidays Settings');
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
