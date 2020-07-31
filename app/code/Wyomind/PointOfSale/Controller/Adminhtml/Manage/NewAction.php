<?php

namespace Wyomind\PointOfSale\Controller\Adminhtml\Manage;

class NewAction extends \Wyomind\PointOfSale\Controller\Adminhtml\PointOfSale
{

    public function execute()
    {
        return $this->_resultForwardFactory->create()->forward("edit");
    }
}
