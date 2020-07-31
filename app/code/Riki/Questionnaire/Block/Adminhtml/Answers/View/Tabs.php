<?php
namespace Riki\Questionnaire\Block\Adminhtml\Answers\View;

/**
 * Class Tabs
 * @package Riki\Questionnaire\Block\Adminhtml\Answers\View
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('answers_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Answers information'));
    }
}