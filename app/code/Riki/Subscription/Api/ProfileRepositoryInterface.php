<?php
namespace Riki\Subscription\Api;
use Magento\Framework\Api\SearchCriteriaInterface;
/**
 * @api
 */
interface ProfileRepositoryInterface
{
    /**
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria
     * @return Data\ApiProfileInterface[]
     */
    public function getList(\Magento\Framework\Api\SearchCriteria $searchCriteria);

    /**
     * @param $id
     * @return Data\ApiProfileInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($id);

    /**
     * @param Data\ApiProfileInterface $profile
     * @return mixed
     */
    public function validate(\Riki\Subscription\Api\Data\ApiProfileInterface $profile);

    /**
     * @param Data\ApiProfileInterface $profile
     * @return mixed
     */
    public function save(\Riki\Subscription\Api\Data\ApiProfileInterface $profile);

    /**
     * @param Data\ApiProfileInterface $profile
     * @return mixed
     */
    public function delete(\Riki\Subscription\Api\Data\ApiProfileInterface $profile);

    /**
     * @param $profileId
     * @return mixed
     */
    public function deleteById($profileId);

    /**
     * @param string $stockpoint
     * @return \Riki\Subscription\Model\Profile\ResourceModel\Profile\Collection
     */
    public function getProfilesByStockPointId($stockpoint);

}