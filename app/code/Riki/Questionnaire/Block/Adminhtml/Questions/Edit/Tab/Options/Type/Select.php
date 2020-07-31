<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * customers defined options
 */
namespace Riki\Questionnaire\Block\Adminhtml\Questions\Edit\Tab\Options\Type;

class Select extends AbstractType
{
    /**
     * @var string
     */
    protected $_template = 'questions/edit/options/type/select.phtml';

    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'add_select_row_button',
            'Magento\Backend\Block\Widget\Button',
            [
                'label' => __('Add New Choice'),
                'class' => 'add add-select-row',
                'id' => 'question_option_<%- data.question_id %>_add_select_row'
            ]
        );

        $this->addChild(
            'delete_select_row_button',
            'Magento\Backend\Block\Widget\Button',
            [
                'label' => __('Delete Choice'),
                'class' => 'delete delete-select-row icon-btn',
                'id' => 'question_option_<%- data.id %>_select_<%- data.select_id %>_delete'
            ]
        );

        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_select_row_button');
    }

    /**
     * @return string
     */
    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_select_row_button');
    }
}
