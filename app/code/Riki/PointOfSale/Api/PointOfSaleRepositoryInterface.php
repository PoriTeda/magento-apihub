<?php
namespace Riki\PointOfSale\Api;

interface PointOfSaleRepositoryInterface
{

    /**
     * @param $id
     * @return mixed
     */
    public function get($id);

    /**
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria);
}