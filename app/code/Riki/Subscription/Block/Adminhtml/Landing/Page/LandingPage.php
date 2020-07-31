<?php

namespace Riki\Subscription\Block\Adminhtml\Landing\Page;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Class Campaign
 *
 * @package Riki\Subscription\Block\Adminhtml\Landing\Page
 */
class LandingPage extends Container
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml';
        $this->_blockGroup = 'Riki_Subscription';
        $this->_headerText = __('Landing Page Management');
        parent::_construct();
    }
}
