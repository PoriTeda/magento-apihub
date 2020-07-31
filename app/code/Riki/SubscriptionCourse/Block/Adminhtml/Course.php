<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SubscriptionCourse\Block\Adminhtml;

/**
 * Adminhtml course blocks content block
 */
class Course extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Riki_SubscriptionCourse';
        $this->_controller = 'adminhtml';
        $this->_headerText = __('Subscription Course');
        $this->_addButtonLabel = __('Add Subscription Course');
        parent::_construct();
    }
}
