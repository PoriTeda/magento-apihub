<?php

namespace Riki\Sales\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Riki\Sales\Api\Data\ShippingReason\ShippingReasonInterface;

interface ShippingReasonRepositoryInterface
{

    /**
     * @param ShippingReasonInterface $reason
     * @return mixed
     */
    public function save(ShippingReasonInterface $reason);


    /**
     * @param $reasonId
     * @return mixed
     */
    public function getById($reasonId);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Riki\Sales\Api\Data\ShippingReason\ShippingReasonSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param ShippingReasonInterface $reason
     * @return mixed
     */
    public function delete(ShippingReasonInterface $reason);

    /**
     * @param $reasonId
     * @return mixed
     */
    public function deleteById($reasonId);
}
