<?php
namespace Riki\Subscription\Block\Adminhtml\Multiple\Category;

/**
 * Class Campaign
 * @package Riki\Subscription\Block\Adminhtml\Multiple\Category
 */
class Campaign extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml';
        $this->_blockGroup = 'Riki_Subscription';
        $this->_headerText = __('Manage Multiple Categories');
        parent::_construct();
    }
}
