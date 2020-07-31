<?php

/*
 * Copyright © 2016 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\AdvancedInventory\Model\ResourceModel\WebsitesLinks;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected function _construct()
    {
        $this->_init('Wyomind\AdvancedInventory\Model\WebsitesLinks', 'Wyomind\AdvancedInventory\Model\ResourceModel\WebsitesLinks');
    }
    
    public function getWebsitesIds($productId)
    {
        
        $connection = $this->_resource;
        $advancedinventoryItem = $connection->getTable('store_website');
        
        $this->getSelect()->reset("columns");
        $this->getSelect()->columns('website.name');
        $this->getSelect()->where("product_id=" . $productId . "");
        $this->getSelect()->joinLeft(["website" => $advancedinventoryItem], "website.website_id = main_table.website_id", ["name"=>"name"]);
        
        return $this;
    }
}
