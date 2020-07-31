<?php
namespace Riki\Rma\Block\Adminhtml;

class Refund extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_controller = 'adminhtml_refund';
        $this->_blockGroup = 'Riki_Rma';
        $this->_headerText = __('Refunds');
        $this->removeButton('add');
    }
}