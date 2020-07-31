<?php
namespace Riki\FairAndSeasonalGift\Block\Adminhtml\Fair\Edit;
/**
 * Class Tabs
 * @package Riki\FairAndSeasonalGift\Block\Adminhtml\Fair\Edit
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('fair_seasonal_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Fair information'));
    }
}