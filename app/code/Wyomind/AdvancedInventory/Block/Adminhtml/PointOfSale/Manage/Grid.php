<?php

/**
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Block\Adminhtml\PointOfSale\Manage;

class Grid
{

    protected $_helperPermissions;

    public function __construct(
        \Wyomind\AdvancedInventory\Helper\Permissions $helperPermissions
    ) {
        $this->_helperPermissions = $helperPermissions;
    }

    public function around_prepareCollection(
        $subject,
        $proceed
    ) {
        $subject = $proceed();
        $collection = $subject->collectionFactory->create();

        $filter = $subject->getParam($subject->getVarNameFilter(), null);
        parse_str(urldecode(base64_decode($filter)), $data);

        if (isset($data['name'])) {
            $collection->addFieldToFilter('name', ['like' => "%" . $data['name'] . "%"]);
        }

        if ($this->_helperPermissions->hasAllPermissions()) {
            return $this;
        }
        $pos = $this->_helperPermissions->getUserPermissions();
        foreach ($pos as $p) {
            $filters[] = ['eq' => $p];
        }
        if (!count($pos)) {
            $filters[] = ['eq' => "No permissions!"];
        }

        if (count($filters)) {
            $collection->addFieldToFilter('place_id', $filters);
        }
        $subject->setCollection($collection);


        return $subject;
    }
}
