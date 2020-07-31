<?php
namespace Riki\ShipLeadTime\Block\Adminhtml\Index\Edit;

use \Magento\Backend\Block\Widget\Form\Generic;

class Form extends Generic
{

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;
    protected $_leadTimeModel;

    /**
     *
     */

    /**
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
        \Riki\ShipLeadTime\Model\Leadtime $leadTimeModel,
        array $data = []
    ) {
        $this->_leadTimeModel = $leadTimeModel;
        $this->_systemStore = $systemStore;
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
        $this->setId('shipleadtime_form');
        $this->setTitle(__('Shipping Lead Time Information'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Riki\ShipLeadTime\Model\Leadtime $model */
        $model = $this->_coreRegistry->registry('riki_shipleadtime');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post','enctype' => 'multipart/form-data']]
        );

        $form->setHtmlIdPrefix('shipleadtime_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information'), 'class' => 'fieldset-wide']
        );

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

        $fieldset->addField(
            'is_active',
            'select',
            [
                'label' => __('Status'),
                'title' => __('Status'),
                'name' => 'is_active',
                'required' => true,
                'options' => ['1' => __('Active'), '0' => __('Inactive')]
            ]
        );

        $fieldset->addField(
            'warehouse_id',
            'select',
            [
                'label' => __('WareHouse'),
                'title' => __('WareHouse'),
                'name' => 'warehouse_id',
                'options' => $this->_leadTimeModel->getWareHouseOptions(),
            ]
        );

        $fieldset->addField(
            'delivery_type_code',
            'select',
            [
                'label' => __('Shipping method/Delivery type'),
                'title' => __('Shipping method/Delivery type'),
                'name' => 'delivery_type_code',
                'options' => $this->_leadTimeModel->getDeliveryType(),
            ]
        );


        $fieldset->addField(
            'pref_id',
            'select',
            [
                'name' => 'pref_id',
                'label' => __('Prefecture'),
                'title' => __('Prefecture'),
                'required' => true,
                'options' => $this->_leadTimeModel->getAllJapanPrefecture(),
            ]
        );


        $fieldset->addField(
            'shipping_lead_time',
            'text',
            [
                'name' => 'shipping_lead_time',
                'label' => __('Shipping Lead Time (Days)'),
                'title' => __('Prefecture Shipping Lead Time'),
                'required' => true,
                'class' => 'validate-number validate-digits validate-greater-than-zero'
            ]
        );
        $fieldset->addField(
            'priority',
            'text',
            [
                'name' => 'priority',
                'label' => __('Priority per Prefecture'),
                'title' => __('Priority per Prefecture'),
                'required' => false,
                'class' => 'validate-number validate-digits validate-greater-than-zero'
            ]
        );
        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}