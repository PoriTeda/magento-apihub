<?php

namespace Wyomind\PointOfSale\Block\Adminhtml\Manage;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{

    protected $_coreRegistry = null;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_blockGroup = 'Wyomind_PointOfSale';
        $this->_controller = 'adminhtml_manage';
        parent::_construct();


        $this->removeButton('save');
        $this->removeButton('reset');

        $this->updateButton('delete', 'label', __('Delete'));

        $this->addButton(
            'save',
            [
            'label' => __('Save'),
            'class' => 'save',
            'onclick' => "jQuery('#back_i').val('1'); jQuery('#edit_form').submit();",
            ]
        );
    }
}
