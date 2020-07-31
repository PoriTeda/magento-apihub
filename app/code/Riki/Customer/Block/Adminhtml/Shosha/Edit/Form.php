<?php
namespace Riki\Customer\Block\Adminhtml\Shosha\Edit;

use \Magento\Backend\Block\Widget\Form\Generic;

class Form extends Generic
{

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Riki\Customer\Model\Shosha\ShoshaCode
     */
    protected $_optionShoshaCode;

    /**
     * @var \Riki\Customer\Model\Shosha\StoreCode
     */
    protected $_optionStoreCode;

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $sourceYesno;

    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Riki\Customer\Model\Shosha\ShoshaCode $optionShoshaCode
     * @param \Riki\Customer\Model\Shosha\StoreCode $optionStoreCode
     * @param \Magento\Config\Model\Config\Source\Yesno $sourceYesno
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Riki\Customer\Model\Shosha\ShoshaCode $optionShoshaCode,
        \Riki\Customer\Model\Shosha\StoreCode  $optionStoreCode,
        \Magento\Config\Model\Config\Source\Yesno $sourceYesno,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;

        $this->_optionShoshaCode = $optionShoshaCode;

        $this->_optionStoreCode = $optionStoreCode;

        $this->sourceYesno = $sourceYesno;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Init form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('shoshacustomer_form');
        $this->setTitle(__('Shosha Buiness Code Information'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {

        $model = $this->_coreRegistry->registry('shoshacustomer');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post']]
        );

        $form->setHtmlIdPrefix('shoshacustomer_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Shosha Business Code Information'), 'class' => 'fieldset-wide']
        );

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }


        $fieldset->addField(
            'shosha_business_code',
            'text',
            ['name' => 'shosha_business_code', 'label' => __('Shosha business code'), 'title' => __('Shosha business code'),'required' => true ,'class' => 'validate-business-code' ]
        );
        $fieldset->addField(
            'shosha_code',
            'select',
            ['name' => 'shosha_code', 'label' => __('Shosha code'), 'title' => __('Company type'),'values' => $this->_optionShoshaCode->getAllOptions() ,'required' => true]
        );

        $fieldset->addField(
            'shosha_cmp',
            'text',
            ['name' => 'shosha_cmp', 'label' => __('Company name'), 'title' => __('Company name'),'class' => 'validate-special-character validate-cedyna-length-shosha_cmp-shosha_dept-shosha_in_charge validate-cedyna-length-shosha_cmp-shosha_dept validate-cedyna-length-shosha_cmp-shosha_in_charge validate-cedyna-length-shosha_cmp validate-cedyna-length-shosha_cmp validate-full-width cedyna-required']
        );

        $fieldset->addField(
            'shosha_cmp_kana',
            'text',
            ['name' => 'shosha_cmp_kana', 'label' => __('Company name - Kana'), 'title' => __('Company name - Kana'),'class' => 'validate-special-character validate-cedyna-length-shosha_cmp_kana-shosha_dept_kana-shosha_in_charge_kana validate-cedyna-length-shosha_cmp_kana-shosha_dept_kana validate-cedyna-length-shosha_cmp_kana-shosha_in_charge_kana validate-cedyna-length-shosha_cmp_kana validate-katakana cedyna-required']
        );

        $fieldset->addField(
            'shosha_dept',
            'text',
            ['name' => 'shosha_dept', 'label' => __('Company department name'), 'title' => __('Company department name'),'class' => 'validate-special-character validate-cedyna-length-shosha_cmp-shosha_dept-shosha_in_charge validate-cedyna-length-shosha_cmp-shosha_dept validate-full-width']
        );

        $fieldset->addField(
            'shosha_dept_kana',
            'text',
            ['name' => 'shosha_dept_kana', 'label' => __('Company department name - Kana'), 'title' => __('Company department name - Kana'),'class' => 'validate-special-character validate-cedyna-length-shosha_cmp_kana-shosha_dept_kana-shosha_in_charge_kana validate-cedyna-length-shosha_cmp_kana-shosha_dept_kana validate-katakana']
        );

        $fieldset->addField(
            'shosha_in_charge',
            'text',
            ['name' => 'shosha_in_charge', 'label' => __('Name of person in charge'), 'title' => __('Name of person in charge') ,'class' => 'validate-special-character validate-cedyna-length-shosha_cmp-shosha_dept-shosha_in_charge validate-cedyna-length-shosha_cmp-shosha_in_charge validate-full-width']
        );

        $fieldset->addField(
            'shosha_in_charge_kana',
            'text',
            ['name' => 'shosha_in_charge_kana', 'label' => __('Name of person in charge - Kana'), 'title' => __('Name of person in charge - Kana') ,'class' => 'validate-special-character validate-cedyna-length-shosha_cmp_kana-shosha_dept_kana-shosha_in_charge_kana validate-cedyna-length-shosha_cmp_kana-shosha_in_charge_kana validate-katakana']
        );

        $fieldset->addField(
            'shosha_postcode',
            'text',
            ['name' => 'shosha_postcode', 'label' => __('Company zipcode'), 'title' => __('Company zipcode'),'class' => 'validate-custom-postal-code cedyna-required']
        );

        $fieldset->addField(
            'shosha_address1',
            'text',
            ['name' => 'shosha_address1', 'label' => __('Company address 1'), 'title' => __('Company address 1'),'class' => 'validate-special-character validate-cedyna-length-shosha_address1-shosha_address2 validate-full-width cedyna-required']
        );
        $fieldset->addField(
            'shosha_address2',
            'text',
            ['name' => 'shosha_address2', 'label' => __('Company address 2'), 'title' => __('Company address 2'),'class' => 'validate-special-character validate-cedyna-length-shosha_address1-shosha_address2 validate-full-width']
        );

        $fieldset->addField(
            'shosha_address1_kana',
            'text',
            ['name' => 'shosha_address1_kana', 'label' => __('Company address 1 - Kana'), 'title' => __('Company address 1 - Kana'),'class' => 'validate-special-character validate-cedyna-length-shosha_address1_kana-shosha_address2_kana validate-katakana-address cedyna-required']
        );

        $fieldset->addField(
            'shosha_address2_kana',
            'text',
            ['name' => 'shosha_address2_kana', 'label' => __('Company address 2 - Kana'), 'title' => __('Company address 2 - Kana'),'class' => 'validate-special-character validate-cedyna-length-shosha_address1_kana-shosha_address2_kana validate-shoshacustomer_shosha_address2_kana validate-shoshacustomer_shosha_address2_kana_special_character validate-katakana-address']
        );

        $fieldset->addField(
            'shosha_phone',
            'text',
            ['name' => 'shosha_phone', 'label' => __('Company phone number'), 'title' => __('Company phone number'),'class' => 'validate-cedyna-length-shoshacustomer_shosha_phone validate-phone-number validate-shoshacustomer_shosha_phone cedyna-required']
        );

        $fieldset->addField(
            'shosha_first_code',
            'select',
            ['name' => 'shosha_first_code', 'label' => __('First code'), 'title' => __('First code') ,'values' => $this->_optionStoreCode->getAllOptions() ,'required' => true]
        );

        $fieldset->addField(
            'shosha_second_code',
            'select',
            ['name' => 'shosha_second_code', 'label' => __('Second code'), 'title' => __('Second code'),'values' => $this->_optionStoreCode->getAllOptions(), 'required' => true]
        );

        $fieldset->addField(
            'shosha_commission',
            'text',
            ['name' => 'shosha_commission', 'label' => __('Commission'), 'title' => __('Commission'),'class' => 'validate-commission', 'required' => true]
        );

        $yesNoOptions = $this->sourceYesno->toOptionArray();

        $fieldset->addField(
            'block_orders',
            'select',
            ['name' => 'block_orders', 'label' => __('Block Orders'), 'title' => __('Block Orders') ,'values' => $yesNoOptions ,'required' => true]
        );

        $fieldset->addField(
            'cedyna_counter',
            'text',
            ['name' => 'cedyna_counter', 'label' => __('Cedyna Monthly Counter'), 'title' => __('Cedyna Monthly Counter'),'class' => 'validate-number', 'required' => false]
        );

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}