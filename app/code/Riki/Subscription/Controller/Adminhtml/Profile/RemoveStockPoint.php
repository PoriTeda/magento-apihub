<?php

namespace Riki\Subscription\Controller\Adminhtml\Profile;

use Magento\Backend\App\Action;

class RemoveStockPoint extends \Magento\Backend\App\Action
{
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $profileRepository;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileData;

    /**
     * @var \Riki\Subscription\Model\ProfileCacheRepository
     */
    protected $profileCacheRepository;

    /**
     * RemoveStockPoint constructor.
     * @param Action\Context $context
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Riki\Subscription\Helper\Profile\Data $subHelperProfile
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
     */
    public function __construct(
        Action\Context $context,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\Subscription\Helper\Profile\Data $subHelperProfile,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
    ) {

        $this->profileData = $subHelperProfile;
        $this->profileRepository = $profileRepository;
        $this->profileCacheRepository = $profileCacheRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $profileId = $this->_request->getParam('id');
        if ($this->profileData->getTmpProfile($profileId) !== false) {
            $profileId = $this->profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        if ($cacheWrapper = $this->profileCacheRepository->getProfileDataCache($profileId)) {
            $hashBucketId = $cacheWrapper->getData('stock_point_profile_bucket_id');
            $cacheWrapper->setData("stock_point_profile_bucket_id", null)
                ->setData("stock_point_delivery_type", null)
                ->setData("stock_point_delivery_information", null)
                ->setData("stock_point_data", null)
                ->setData("riki_stock_point_id", null)
                ->setData("is_delete_stock_point", true)
                ->setData("delete_profile_has_bucket_id", $hashBucketId);

            $result['status'] = true;
            $this->getMessageManager()->addSuccessMessage(
                __('Stock Point temporarily removed, please click "Confirm Changes" button to delete actualy')
            );

            $this->profileCacheRepository->save($cacheWrapper);
            return $this->getResponse()->setBody(\Zend_Json::encode($result));
        }
        $result['status'] = false;
        $this->getMessageManager()->addErrorMessage(
            __("Some thing went wrong, please reload page.")
        );

        return $this->getResponse()->setBody(\Zend_Json::encode($result));
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::profile_edit');
    }
}
