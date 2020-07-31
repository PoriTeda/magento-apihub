<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Model;

class Journal extends \Magento\Framework\Model\AbstractModel
{

    protected $_helperCore;
    protected $_coreDate;

    public function _construct()
    {
        $this->_init('Wyomind\AdvancedInventory\Model\ResourceModel\Journal');
    }

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Wyomind\Core\Helper\Data $helperCore,
        \Magento\Framework\Stdlib\DateTime\DateTime $coreDate,
        \Magento\Framework\Model\ResourceModel\AbstractResource $abstractResource = null,
        \Magento\Framework\Data\Collection\AbstractDb $abstactDb = null,
        array $data = []
    ) {
        $this->_helperCore = $helperCore;
        $this->_coreDate = $coreDate;
        parent::__construct($context, $registry, $abstractResource, $abstactDb, $data);
    }

    public function getColumn($column)
    {
        $collection = $this->getCollection()->getColumn($column);
        return $collection;
    }

    public function getUsers()
    {
        $array = [];
        foreach ($this->getColumn('user') as $line) {
            $array[$line->getUser()] = $line->getUser();
        }
        return $array;
    }

    public function getActions()
    {
        $array = [];
        foreach ($this->getColumn('action') as $line) {
            $array[$line->getAction()] = $line->getAction();
        }
        return $array;
    }

    public function getContexts()
    {
        $array = [];
        foreach ($this->getColumn('context') as $line) {
            $array[$line->getContext()] = $line->getContext();
        }
        return $array;
    }

    public function clean()
    {
        $history = $this->_helperCore->getStoreConfig("advancedinventory/system/journal_lifetime");

        $todayTimestamp = $this->_coreDate->gmtTimestamp();
        $historyTimestamp = $todayTimestamp - $history * 86400;
        $historyDatetime = $this->_coreDate->gmtDate('Y-m-d H:i:s', $historyTimestamp);
        $collection = $this->getCollection()->addFieldToFilter("datetime", ["lt" => $historyDatetime]);
        try {
            foreach ($collection as $log) {
                $log->delete();
            }
        } catch (\Exception $e) {
            throw new \Exception("Advanced Inventory > " . $e->getMessage());
        }
    }
}
