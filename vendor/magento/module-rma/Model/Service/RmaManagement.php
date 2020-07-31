<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Model\Service;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Rma\Api\Data\RmaSearchResultInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Api\RmaManagementInterface;
use Magento\Rma\Model\Rma\PermissionChecker;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Authorization\Model\UserContextInterface;

/**
 * Class RmaManagement
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RmaManagement implements RmaManagementInterface
{
    /**
     * Permission checker
     *
     * @var PermissionChecker
     */
    protected $permissionChecker;

    /**
     * Rma repository
     *
     * @var RmaRepositoryInterface
     */
    protected $rmaRepository;

    /**
     * User context
     *
     * @var UserContextInterface
     *
     * @deprecated 101.0.0 As this property isn't used anymore
     */
    protected $userContext;

    /**
     * Filter builder
     *
     * @var FilterBuilder
     *
     * @deprecated 101.0.0 As this property isn't used anymore
     */
    protected $filterBuilder;

    /**
     * Search criteria builder
     *
     * @var SearchCriteriaBuilder
     *
     * @deprecated 101.0.0 As this property isn't used anymore
     */
    protected $criteriaBuilder;

    /**
     * Constructor
     *
     * @param PermissionChecker $permissionChecker
     * @param RmaRepositoryInterface $rmaRepository
     * @param UserContextInterface $userContext
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(
        PermissionChecker $permissionChecker,
        RmaRepositoryInterface $rmaRepository,
        UserContextInterface $userContext,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $criteriaBuilder
    ) {
        $this->permissionChecker = $permissionChecker;
        $this->rmaRepository = $rmaRepository;
        $this->userContext = $userContext;
        $this->filterBuilder = $filterBuilder;
        $this->criteriaBuilder = $criteriaBuilder;
    }

    /**
     * Save RMA
     *
     * @param \Magento\Rma\Api\Data\RmaInterface $rmaDataObject
     * @return \Magento\Rma\Api\Data\RmaInterface
     */
    public function saveRma(\Magento\Rma\Api\Data\RmaInterface $rmaDataObject)
    {
        $this->permissionChecker->checkRmaForCustomerContext();
        return $this->rmaRepository->save($rmaDataObject);
    }

    /**
     * Return list of rma data objects based on search criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria search criteria object
     * @return RmaSearchResultInterface rma search result
     */
    public function search(SearchCriteriaInterface $searchCriteria)
    {
        $this->permissionChecker->checkRmaForCustomerContext();

        return $this->rmaRepository->getList($searchCriteria);
    }
}
