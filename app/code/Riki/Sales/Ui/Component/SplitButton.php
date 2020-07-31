<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Sales\Ui\Component;

use \Riki\Sales\Helper\CheckRoleViewOnly;

/**
 * Split button widget
 */
class SplitButton extends \Magento\Backend\Block\Widget\Button\SplitButton
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var CheckRoleViewOnly
     */
    protected $checkRoleOnly;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        CheckRoleViewOnly $checkRoleOnly,
        array $data = []
    )
    {
        $this->registry = $coreRegistry;
        $this->checkRoleOnly = $checkRoleOnly;
        parent::__construct($context,$data);
    }

    protected function _toHtml()
    {
        if ($this->checkRoleOnly->checkViewShipmentOnly(CheckRoleViewOnly::ORDER_GIRD_VIEW_ONLY)){
            return '';
        }

        return parent::_toHtml();
    }
}
