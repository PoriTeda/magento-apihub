<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Helper;

class Permissions extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_permissions = [];
    protected $_coreHelper = null;
    protected $_auth = null;
    protected $_objectManager = null;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Wyomind\Core\Helper\Data $coreHelper,
        \Magento\Backend\Model\Auth $auth,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_coreHelper = $coreHelper;
        $this->_auth = $auth;
        $this->_objectManager = $objectManager;

        if ($this->_permissions == null) {
            $permissions = $this->_objectManager->create('Magento\Framework\Json\Helper\Data')->jsonDecode($this->_coreHelper->getDefaultConfig("advancedinventory/system/permissions"));

            if ($this->_auth->isLoggedIn()) {
                $userId = $this->_auth->getUser()->getUserId();
                if (isset($permissions[$userId])) {
                    $this->_permissions = $permissions[$userId];
                }
            }
        }

        parent::__construct($context);
    }

    public function getUserPermissions()
    {
        return $this->_permissions;
    }

    public function hasAllPermissions()
    {
        if (count($this->_permissions) && $this->_permissions[0] == "all") {
            return true;
        }
        return false;
    }

    public function canSeeUnassignedOrders()
    {
        if ($this->_permissions[1] == "na") {
            return true;
        }
        return false;
    }

    public function isAllowed($pos)
    {

        if ($this->hasAllPermissions() || in_array($pos, $this->getUserPermissions())) {
            return true;
        }
        return false;
    }
}
