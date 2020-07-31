<?php

namespace Riki\Catalog\Observer\Adminhtml;

class CatalogProductIsSalableAfter extends \Riki\AdvancedInventory\Observer\CatalogProductIsSalableAfter
{
    /**
     * set list point of sales before get product stock status
     *      scope is adminhtml, will get all point of sales
     */
    public function setPlaceIds()
    {
        $this->placeIds = [];
    }
}