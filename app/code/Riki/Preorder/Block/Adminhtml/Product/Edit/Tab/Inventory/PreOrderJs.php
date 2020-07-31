<?php

namespace Riki\Preorder\Block\Adminhtml\Product\Edit\Tab\Inventory;

class PreOrderJs extends \Magento\Backend\Block\Template
{

    public function getPreorderId()
    {
        return \Riki\Preorder\Helper\Data::BACKORDERS_PREORDER_OPTION;
    }

}
