<?php

namespace Riki\BackOrder\Block\Adminhtml\Product\Edit\Tab\Inventory;

class BackOrderJs extends \Magento\Backend\Block\Template
{

    public function getPreorderId()
    {
        return \Riki\Preorder\Helper\Data::BACKORDERS_PREORDER_OPTION;
    }

}
