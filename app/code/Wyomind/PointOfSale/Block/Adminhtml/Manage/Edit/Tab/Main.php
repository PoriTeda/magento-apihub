<?php

namespace Wyomind\PointOfSale\Block\Adminhtml\Manage\Edit\Tab;

class Main extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{

    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('pointofsale');

        $form = $this->_formFactory->create();


        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('General Information')]);

        if ($model->getPlaceId()) {
            $fieldset->addField('place_id', 'hidden', ['name' => 'place_id']);
        }


        // ===================== action flags ==================================
        // save and continue flag
        $fieldset->addField('back_i', 'hidden', ['name' => 'back_i', 'value' => '']);


        $fieldset->addField(
            'store_code',
            'text',
            [
            'label' => __('Code (internal use)'),
            'name' => 'store_code',
            'class' => 'required-entry',
            'required' => true,
            ]
        );

        $fieldset->addField(
            'name',
            'text',
            [
            'label' => __('Name'),
            'name' => 'name',
            'class' => 'required-entry',
            'required' => true,
            ]
        );


        $fieldset->addField(
            'status',
            'select',
            [
                'label' => __('Type of display'),
                'name' => 'status',
                'values' => [
                    [
                        'value' => 0,
                        'label' => __('Warehouse (not visible on the Gmap/checkout)'),
                    ],
                    [
                        'value' => 1,
                        'label' => __('Point of Sales (visible on the Gmap/checkout)'),
                    ],
                ]
            ]
        );
        $fieldset->addField(
            'position',
            'text',
            [
            'label' => __('Order of display'),
            'name' => 'position',
            'class' => 'required-entry validate-number',
            'required' => true,
            ]
        );



        $fieldset->addField(
            'latitude',
            'text',
            [
            'label' => __('Latitude'),
            'class' => 'validate-number',
            'name' => 'latitude',
            'class' => 'required-entry validate-number',
            'required' => true,
            ]
        );

        $fieldset->addField(
            'longitude',
            'text',
            [
            'label' => __('Longitude'),
            'class' => 'validate-number',
            'name' => 'longitude',
            'class' => 'required-entry validate-number',
            'required' => true,
            'after_element_html' => '  
                <div style="margin:10px  0px ;"><b>' . __("Find the coordinates with Google Map:") . '</b> </div>
                <div id="map" ></div>'
            ]
        );


        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getTabLabel()
    {
        return __('General Information');
    }

    public function getTabTitle()
    {
        return __('General Information');
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
