<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Model;

class Notifications
{

    protected $_coreHelperData = null;
    protected $_urlInterface = null;
    protected $_session = null;
    protected $_warnings = 0;
    protected $_ids = null;

    public function __construct(
        \Wyomind\Core\Helper\Data $coreHelperData,
        \Magento\Framework\UrlInterface $urlInterface,
        \Wyomind\AdvancedInventory\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Wyomind\AdvancedInventory\Helper\Permissions $helperPermissions,
        \Magento\Backend\Model\Session $session
    ) {

        $this->_coreHelperData = $coreHelperData;
        $this->_urlInterface = $urlInterface;
        $this->_session = $session;


        if ($helperPermissions->hasAllPermissions()) {
            $dateConfig = $this->_coreHelperData->getStoreConfig("advancedinventory/settings/order_notification_from_date");
            $statuses = explode(",", $this->_coreHelperData->getStoreConfig("advancedinventory/settings/disallow_assignation_status"));

            $data = $orderCollectionFactory->create()->getCountNotAssigned($dateConfig, $statuses);

            $this->_warnings = $data['count'];
            $this->_ids = $data['ids'];
        }
    }

    public function afterGetText(
        $object,
        $return
    ) {

        $html = null;

        if ($this->_warnings > 0 && $this->_coreHelperData->getStoreConfig("advancedinventory/settings/order_notification")) {
            ($this->_warnings > 1) ? $s = "s" : $s = "";

            $style = null;
            if ($return != null) {
                $style = "padding-top:5px;margin-top:5px;border-top:1px solid gray;";
            }
            if (!$this->_session->getData("selected_ids")) {
                $url = $this->_urlInterface->getUrl('advancedinventory/sales/index', ['ids' => base64_encode($this->_ids)]);
                $message = __('Manage these orders');
            } else {
                $url = $this->_urlInterface->getUrl('advancedinventory/sales/ignore');
                $message = __('Ignore');
            }
            $html.= "<div style='$style'>"
                    . $this->_warnings . " " . __(" of your order$s require your attention.")
                    . " <a href='" . $url . "'>" . $message . "</a>
                  
            </div>";

            return $return . $html;
        }
        return $return;
    }

    public function afterIsDisplayed(
        $object,
        $return
    ) {
        return $return || $this->_warnings;
    }
}
