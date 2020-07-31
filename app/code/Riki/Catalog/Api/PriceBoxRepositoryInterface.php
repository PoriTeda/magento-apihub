<?php
namespace Riki\Catalog\Api;

interface PriceBoxRepositoryInterface
{
    /**
     * Get price box data of product
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return \Riki\Catalog\Api\Data\PriceBoxInterface[]
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);
}