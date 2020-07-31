<?php
namespace Riki\Subscription\Api\WebApi;
use Magento\Framework\Api\SearchCriteriaInterface;
use Riki\Subscription\Model\Profile\WebApi\ProfileResults;
/**
 * @api
 */
interface ProfileRepositoryInterface
{
    /**
     * Get profile list
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Riki\Subscription\Api\Data\ProfileSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Get profiles by consumer ID
     *
     * @param string $consumerId
     * @return mixed[]
     */
    public function get($consumerId);

    /**
     * Get profile by profile ID
     *
     * @param int $id
     * @return \Riki\Subscription\Api\Data\ProfileInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function load($id);

    /**
     * get date range profile data by profile ID
     *
     * @param int $profileId
     * @return string[]
     */
    public function getDateRange($profileId);

    /**
     * get next delivery date by profile ID
     *
     * @param int $profileId
     * @return string[]
     */
    public function getNextDate($profileId);


    /**
     * Get subscription course frequency by profile ID
     *
     * @param int $profileId
     * @return string[]
     */
    public function getFrequency($profileId);

    /**
     * Validate profile data by profile ID
     *
     * @param \Riki\Subscription\Api\Data\ProfileInterface $profile
     * @param int $profileId
     * @return string[]
     */
    public function validate($profile, $profileId);

    /**
     * Update profile data by profile ID
     *
     * @param \Riki\Subscription\Api\Data\ProfileInterface $profile
     * @param int $profileId
     * @return string[]
     */
    public function update($profile, $profileId);

    /**
     * Save Subscription Profile
     * @param $profileData
     * @param $method
     * @param $arrAddress
     * @param $type
     * @return mixed
     */
    public function save($profileData,$method,$arrAddress,$type);

    /**
     * Get  Min Date in Array Delivery Date
     *
     * @param $arrObjProductCat
     * @return mixed
     */
    public function _minDate($arrObjProductCat);

    /**
     *Get Min Delivery Date
     *
     * @param $arrDate
     * @return mixed
     */
    public function _getMinDate($arrDate);

    /**
     * Send Email when edit subscription profile
     *
     * @param $profile
     * @return mixed
     */
    public function _sendEmailEditProfile($profile);

    /**
     * Update sub total when customer change product qty
     *
     * @api
     *
     * @param int $courseId
     * @param int $iProfileId
     * @param string $frequencyUnit
     * @param string $frequencyInterval
     * @param int $productId
     * @param int $qtyChange
     * @return mixed
     */
    public function changeProductQty($courseId, $iProfileId, $frequencyUnit, $frequencyInterval, $productId, $qtyChange);

    /**
     * @api
     *
     * @param int $customerId
     * @param int $campaignId
     * @return string[]
     */
    public function getMultipleCategoryCampaignProfileByCustomer($customerId, $campaignId);

    /**
     * @param int $customerId
     * @param int $landingPageId
     * @return string[]
     * @api
     */
    public function getProfileByCustomerForSummerCampaign($customerId, $landingPageId);
}