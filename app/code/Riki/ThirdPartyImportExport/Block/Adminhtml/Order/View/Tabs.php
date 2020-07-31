<?php
namespace Riki\ThirdPartyImportExport\Block\Adminhtml\Order\View;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Initialize
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setTitle(__('Order View'));
        $this->setId('thirdpartyimportexport_order_view_tabs');
        $this->setDestElementId('thirdpartyimportexport_order_view');
    }
}
