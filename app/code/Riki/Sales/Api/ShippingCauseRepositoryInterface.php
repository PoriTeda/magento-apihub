<?php

namespace Riki\Sales\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Riki\Sales\Api\Data\ShippingCause\ShippingCauseInterface;

interface ShippingCauseRepositoryInterface
{

    /**
     * @param ShippingCauseInterface $cause
     * @return mixed
     */
    public function save(ShippingCauseInterface $cause);


    /**
     * @param $causeId
     * @return mixed
     */
    public function getById($causeId);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Riki\Sales\Api\Data\ShippingCause\ShippingCauseSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param ShippingCauseInterface $cause
     * @return mixed
     */
    public function delete(ShippingCauseInterface $cause);

    /**
     * @param $causeId
     * @return mixed
     */
    public function deleteById($causeId);
}
