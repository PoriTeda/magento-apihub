<?php
namespace Riki\CedynaInvoice\Block\Adminhtml;

/**
 * Class Invoice
 * @package Riki\CedynaInvoice\Block\Adminhtml
 */
class Invoice extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml';
        $this->_blockGroup = 'Riki_CedynaInvoice';
        $this->_headerText = __('Cedyna Invoice');
        parent::_construct();
    }
}
