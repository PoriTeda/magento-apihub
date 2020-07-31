<?php
namespace Riki\FairAndSeasonalGift\Block\Adminhtml;

class Fair extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_fair';
        $this->_blockGroup = 'Riki_FairAndSeasonalGift';
        $this->_addButtonLabel = __('Add New Fair');

        parent::_construct();
    }
}
