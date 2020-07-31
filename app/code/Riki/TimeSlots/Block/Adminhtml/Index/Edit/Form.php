<?php
namespace Riki\TimeSlots\Block\Adminhtml\Index\Edit;

use \Magento\Backend\Block\Widget\Form\Generic;

class Form extends Generic
{

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;
    protected $_timeSlotsModel;
    protected $_helper;

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
        \Riki\TimeSlots\Model\TimeSlots $timeSlotsModel,
        \Riki\TimeSlots\Helper\Data $helper,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_timeSlotsModel = $timeSlotsModel;
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
        $this->setId('timeslots_form');
        $this->setTitle(__('Time Slots Information'));
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /** @var \Riki\TimeSlots\Model\TimeSlots $model */
        $model = $this->_coreRegistry->registry('riki_timeslots');


        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post','enctype' => 'multipart/form-data']]
        );

        $form->setHtmlIdPrefix('timeslots_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('General Information'), 'class' => 'fieldset-wide']
        );

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }

        $fieldset->addField(
            'position',
            'text',
            [
                'name' => 'position',
                'label' => __('Position'),
                'title' => __('Position'),
                'required' => false,
                'class' => 'validate-number validate-digits validate-zero-or-greater'
            ]
        );

        $fieldset->addField(
            'slot_name',
            'text',
            [
                'name' => 'slot_name',
                'label' => __('Slots name'),
                'title' => __('Slots name'),
                'required' => true
            ]
        );

        $fieldset->addField(
            'from',
            'text',
            [
                'name' => 'from',
                'label' => __('From'),
                'title' => __('From'),
                'after_element_html' => __("hh:mm"),
                'required' => true,
                'class' => 'validate-time-24-hour'
            ]
        );

        $fieldset->addField(
            'to',
            'text',
            [
                'name' => 'to',
                'label' => __('To'),
                'title' => __('To'),
                'after_element_html' => __("hh:mm"),
                'required' => true,
                'class' => 'validate-time-24-hour'
            ]

        );

        $fieldRenderFrom = $fieldset->addField('add_js',
            'text',
            [
                'name' => 'add_js',
                'title' => __('Render Addjs')
            ]
        );
        $render = $this->getLayout()->createBlock('Riki\TimeSlots\Block\Adminhtml\Form\Renderer\AddJsValidation');
        $fieldRenderFrom->setRenderer($render);

        $fieldset->addField(
            'appointed_time_slot',
            'text',
            [
                'name' => 'appointed_time_slot',
                'label' => __('Appointed Time slot'),
                'title' => __('Appointed Time slot'),
                'required' => true,
                'after_element_html' => __("Appointed Time Slot is used in sending information to 3PLWH "),
            ]
        );


        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}