<?php

namespace Riki\CatalogRule\Api;

interface ProductRepositoryInterface
{
    /**
     * Get list product
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Riki\CatalogRule\Api\Data\ProductInterface[]|[]
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}
