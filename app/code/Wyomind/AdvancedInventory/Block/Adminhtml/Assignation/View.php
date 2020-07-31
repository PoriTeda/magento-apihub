<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Block\Adminhtml\Assignation;

/**
 * Report block
 */
class View extends \Magento\Backend\Block\Template
{

    public $order = null;
    public $orderId = null;
    public $helperAssignation;
    public $modelAssignation;
    public $modelStock;
    public $permissions;
    public $helperData;
    public $modelPos;
    public $helperCore;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Wyomind\AdvancedInventory\Helper\Assignation $helperAssignation,
        \Wyomind\AdvancedInventory\Model\Assignation $modelAssignation,
        \Wyomind\AdvancedInventory\Model\Stock $modelStock,
        \Wyomind\AdvancedInventory\Helper\Permissions $permissions,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Wyomind\PointOfSale\Model\PointOfSale $modelPos,
        \Wyomind\Core\Helper\Data $helperCore,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helperAssignation = $helperAssignation;
        $this->modelAssignation = $modelAssignation;
        $this->modelStock = $modelStock;
        $this->permissions = $permissions;
        $this->helperData = $helperData;
        $this->modelPos = $modelPos;
        $this->helperCore = $helperCore;
        $orderId = $this->getRequest()->getParam("order_id");
        $this->setOrder($orderFactory->create()->load($orderId));
        $this->setOrderId($orderId);
    }

    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }
}
