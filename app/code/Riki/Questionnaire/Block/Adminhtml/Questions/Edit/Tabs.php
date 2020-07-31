<?php
namespace Riki\Questionnaire\Block\Adminhtml\Questions\Edit;
/**
 * Class Tabs
 * @package Riki\Questionnaire\Block\Adminhtml\Questions\Edit
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('questionnaire_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Questionnaire information'));
    }
}