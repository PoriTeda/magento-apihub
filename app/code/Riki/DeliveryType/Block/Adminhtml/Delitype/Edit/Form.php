<?php
namespace Riki\DeliveryType\Block\Adminhtml\Delitype\Edit;

use \Magento\Backend\Block\Widget\Form\Generic;

class Form extends Generic
{

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

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
        array $data = []
    ) {
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
        $this->setId('delitype_form');
        $this->setTitle(__('Delivery Types Information'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Riki\ShipLeadTime\Model\Leadtime $model */
        $model = $this->_coreRegistry->registry('riki_deliverytype');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post','enctype' => 'multipart/form-data']]
        );

        $form->setHtmlIdPrefix('deliverytype_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information'), 'class' => 'fieldset-wide']
        );

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

//        $fieldset->addField(
//            'code',
//            'text',
//            ['name' => 'code', 'label' => __('Code'), 'title' => __('Code'), 'required' => true]
//        );

        $fieldset->addField(
            'name',
            'text',
            ['name' => 'name', 'label' => __('Delivery type'), 'title' => __('Delivery type'), 'required' => true]
        );

        $fieldset->addField(
            'shipping_fee',
            'text',
            ['name' => 'shipping_fee', 'label' => __('Fee per delivery type (JPY)'), 'title' => __('Fee per delivery type (JPY)'), 'required' => true,'class' => 'integer']
        );

        $fieldset->addField(
            'sync_code',
            'text',
            ['name' => 'sync_code', 'label' => __('Code Sync with 3PLWH'), 'title' => __('Code Sync with 3PLWH'), 'required' => true]
        );
        $fieldset->addField(
            'description',
            'text',
            ['name' => 'description', 'label' => __('Description'), 'title' => __('Description'), 'required' => false]
        );

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}