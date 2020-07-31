<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\GiftWrapping\Block\Adminhtml\Edit;

class Form extends \Magento\GiftWrapping\Block\Adminhtml\Giftwrapping\Edit\Form
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $_directoryHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Directory\Helper\Data $directoryHelper,
        array $data = []
    ) {
        $this->_systemStore = $systemStore;
        $this->_directoryHelper = $directoryHelper;
        parent::__construct($context, $registry, $formFactory,$systemStore,$directoryHelper, $data);
    }
    /**
     * Prepare edit form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_giftwrapping_model');

        $actionParams = ['store' => $model->getStoreId()];
        if ($model->getId()) {
            $actionParams['id'] = $model->getId();
        }
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('adminhtml/*/save', $actionParams),
                    'method' => 'post',
                    'field_name_suffix' => 'wrapping',
                    'enctype' => 'multipart/form-data',
                ],
            ]
        );

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('Gift Wrapping Information')]);
        $this->_addElementTypes($fieldset);

        $fieldset->addField(
            'design',
            'text',
            [
                'label' => __('Gift Wrapping Design'),
                'name' => 'design',
                'required' => true,
                'value' => $model->getDesign(),
                'scope' => 'store'
            ]
        );
        $fieldset->addField(
            'gift_code',
            'text',
            [
                'label' => __('Gift Code'),
                'name' => 'gift_code',
                'required' => true,
                'value' => $model->getGiftCode(),
                'scope' => 'store'
            ]
        );
        $fieldset->addField(
            'gift_name',
            'text',
            [
                'label' => __('Gift Name'),
                'name' => 'gift_name',
                'required' => true,
                'value' => $model->getGiftName(),
                'scope' => 'store'
            ]
        );
        $fieldset->addField(
            'sap_code',
            'text',
            [
                'label' => __('Sap Code'),
                'name' => 'sap_code',
                'required' => true,
                'value' => $model->getSapCode(),
                'scope' => 'store'
            ]
        );


        if (!$this->_storeManager->isSingleStoreMode()) {
            $field = $fieldset->addField(
                'website_ids',
                'multiselect',
                [
                    'name' => 'website_ids',
                    'required' => true,
                    'label' => __('Websites'),
                    'values' => $this->_systemStore->getWebsiteValuesForForm(),
                    'value' => $model->getWebsiteIds()
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element'
            );
            $field->setRenderer($renderer);
        }

        $fieldset->addField(
            'status',
            'select',
            [
                'label' => __('Status'),
                'name' => 'status',
                'required' => true,
                'options' => ['1' => __('Enabled'), '0' => __('Disabled')]
            ]
        );

        $fieldset->addType('price', 'Magento\Catalog\Block\Adminhtml\Product\Helper\Form\Price');
        $fieldset->addField(
            'base_price',
            'price',
            [
                'label' => __('Price'),
                'name' => 'base_price',
                'required' => true,
                'class' => 'validate-not-negative-number',
                'after_element_html' => '<strong>[' . $this->_directoryHelper->getBaseCurrencyCode() . ']</strong>'
            ]
        );

        $fieldset->addField(
            'image',
            'image',
            ['label' => __('Image'), 'name' => 'image_name']
        );

        if (!$model->getId()) {
            $model->setData('status', '1');
        }

        if ($model->hasTmpImage()) {
            $fieldset->addField('tmp_image', 'hidden', ['name' => 'tmp_image']);
        }
        $this->setForm($form);
        $form->setValues($model->getData());
        $form->setDataObject($model);
        $form->setUseContainer(true);
        return $this;;
    }

    /**
     * Retrieve Additional Element Types
     *
     * @return array
     * @codeCoverageIgnore
     */
    protected function _getAdditionalElementTypes()
    {
        return ['image' => 'Magento\GiftWrapping\Block\Adminhtml\Giftwrapping\Helper\Image'];
    }
}
