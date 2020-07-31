<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Sales\Block\Adminhtml\Order\View;
use \Riki\Sales\Helper\CheckRoleViewOnly;

/**
 * Order view tabs
 */
class Tabs extends \Magento\Sales\Block\Adminhtml\Order\View\Tabs
{
    /**
     * @var CheckRoleViewOnly
     */
    protected $checkRoleOnly;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Registry $registry,
        CheckRoleViewOnly $checkRoleOnly,
        array $data = []
    )
    {
        $this->checkRoleOnly = $checkRoleOnly;
        return parent::__construct($context, $jsonEncoder, $authSession, $registry, $data);
    }

    public function canShowTab($tab)
    {
        if ($this->checkRoleOnly->checkViewShipmentOnly(CheckRoleViewOnly::ORDER_VIEW_ONLY))
        {
            if  ($tab->getNameInLayout() !='order_tab_info' ){
                return false;
            }
        }
        return parent::canShowTab($tab);
    }

}
