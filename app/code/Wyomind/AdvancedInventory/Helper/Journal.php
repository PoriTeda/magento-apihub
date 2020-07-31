<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Helper;

class Journal extends \Magento\Framework\App\Helper\AbstractHelper
{

    const SOURCE_PRODUCT = "Product Page";
    const SOURCE_STOCK = "Stock Grid";
    const SOURCE_ORDER = "Order Page";
    const SOURCE_PURCHASE = "New order";
    const SOURCE_REFUND = "Refund";
    const SOURCE_CANCEL = "Order cancelled";
    const SOURCE_POS = "Point of Sale Page";
    const SOURCE_API = "API";
    const ACTION_MASS_UPDATE = "Mass action";
    const ACTION_MULTISTOCK = "Product Multistock";
    const ACTION_IS_IN_STOCK = "Product Is in stock";
    const ACTION_BACKORDERS = "Product Backorders";
    const ACTION_USE_CONFIG_BACKORDERS = "Product Use Config for Backorders";
    const ACTION_QTY = "Product Quantity";
    const ACTION_STOCK_BACKORDERS = "POS/WH backorders";
    const ACTION_STOCK_USE_CONFIG_BACKORDERS = "POS/WH Use Config for Backorders";
    const ACTION_STOCK_QTY = "POS/WH Quantity";
    const ACTION_STOCK_MANAGE_QTY = "POS/WH Manage Quantity";
    const ACTION_ASSIGNATION = "Assignation ";

    protected $_helperCore;
    protected $_coreDate;
    protected $_journalModel;
    protected $_customerSession;
    protected $_auth;

    public function __construct(
        \Wyomind\Core\Helper\Data $helperCore,
        \Magento\Framework\Stdlib\DateTime\DateTime $coreDate,
        \Wyomind\AdvancedInventory\Model\Journal $journalModel,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Backend\Model\Auth $auth
    ) {
        $this->_helperCore = $helperCore;
        $this->_coreDate = $coreDate;
        $this->_journalModel = $journalModel;
        $this->_customerSession = $customerSession;
        $this->_auth = $auth;
    }

    public function insertRow(
        $context,
        $action,
        $reference,
        $values = ['from' => null, "to" => null],
        $user = false
    ) {
        if ($this->_helperCore->getStoreConfig("advancedinventory/system/journal_enabled")) {
            if ($user == false) {
                if ($this->_helperCore->isAdmin()) {
                    $user = "Admin : " . $this->_auth->getUser()->getUsername();
                } else {
                    try {
                        if ($this->_customerSession->isLoggedIn()) {
                            $customer = $this->_customerSession->getCustomer();
                            $user = "Customer : " . $customer->getName();
                        } else {
                            $user = "Customer : Guest";
                        }
                    } catch (\Exception $exception) {
                        $user = "SYSTEM";
                    }
                }
            }


            $datetime = $this->_coreDate->date('Y-m-d H:i:s', $this->_coreDate->gmtTimestamp());
            $data = [
                "user" => $user,
                "datetime" => $datetime,
                "context" => $context,
                "action" => $action,
                "reference" => $reference,
                "details" => $values['from'] . " > " . $values["to"],
            ];

            try {
                $this->_journalModel->setData($data)->save();
            } catch (\Exception $exception) {
                throw new \Exception('Advanced Inventory > Unable to write in journal.');
            }
        }
    }
}
