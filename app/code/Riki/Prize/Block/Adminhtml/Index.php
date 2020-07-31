<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Prize\Block\Adminhtml;

/**
 * Adminhtml course blocks content block
 */
class Index extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Riki_Prize';
        $this->_controller = 'adminhtml_prize';
        $this->_headerText = __('Customer Prize');
        $this->_addButtonLabel = __('Add Prize');
        parent::_construct();
    }
}
