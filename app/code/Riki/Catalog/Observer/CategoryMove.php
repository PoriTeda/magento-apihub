<?php

namespace Riki\Catalog\Observer;

use Magento\Framework\Event\ObserverInterface;

class CategoryMove implements ObserverInterface
{

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $observer->getEvent()->getCategory();

        $pathIds = explode('/', $category->getPath());

        if(count($pathIds) > 1 && $pathIds[count($pathIds) - 2] != $category->getParentId()){
            $e = new \Exception(__('Save category #%1: path incorrect', $category->getId()));
            throw $e;
        }

        return $this;
    }
}
