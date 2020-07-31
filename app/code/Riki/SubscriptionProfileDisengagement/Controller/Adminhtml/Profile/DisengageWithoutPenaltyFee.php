<?php
namespace Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Profile;

class DisengageWithoutPenaltyFee extends \Magento\Backend\App\Action
{
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $_profileFactory;

    /**
     * @var \Riki\SubscriptionProfileDisengagement\Model\ReasonFactory
     */
    protected $_reasonFactory;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_profileHelper;

    /**
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    protected $buildStockPointPostData;

    protected $profileCacheRepository;

    /**
     * DisengageWithoutPenaltyFee constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\SubscriptionProfileDisengagement\Model\ReasonFactory $reasonFactory
     * @param \Riki\Subscription\Helper\Profile\Data $profileHelper
     * @param \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\SubscriptionProfileDisengagement\Model\ReasonFactory $reasonFactory,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
    ) {
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->_profileFactory = $profileFactory;
        $this->_reasonFactory = $reasonFactory;
        $this->_profileHelper = $profileHelper;
        $this->profileCacheRepository = $profileCacheRepository;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->_initProfile();
        $profileId = (int)$this->getRequest()->getParam('id', 0);
        if ($this->_profileHelper->isTmpProfileId($profileId)) {
            $profileId = $this->_profileHelper->getMainFromTmpProfile($profileId);
        }
        $result->setUrl($this->getUrl('profile/profile/edit', ['id' => $profileId]));
        if ($profile) {
            $isStockPoint = $profile->isStockPointProfile();
            $connection = $profile->getResource()->getConnection();
            $connection->beginTransaction();

            try {
                $profile->setStatus(\Riki\Subscription\Model\Profile\Profile::STATUS_DISABLED)
                    ->setStockPointProfileBucketId(null)
                    ->setStockPointDeliveryType(null)
                    ->setStockPointDeliveryInformation(null)
                    ->setSpecifiedWarehouseId(null);

                $profile->save();

                $this->_eventManager->dispatch('subscription_profile_disengaged_without_penalty_after', [
                    'profile' => $profile
                ]);
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We can not disengage this subscription profile now.'));
                $connection->rollBack();
                return $result;
            }

            if ($profile->getType() == \Riki\Subscription\Model\Profile\Profile::SUBSCRIPTION_TYPE_TMP
            ) {
                $mainProfile = $this->_profileHelper->getProfileMainByProfileTmpId($profile->getId());

                if ($mainProfile) {
                    $mainProfile->setStatus(\Riki\Subscription\Model\Profile\Profile::STATUS_DISABLED)
                        ->setStockPointProfileBucketId(null)
                        ->setStockPointDeliveryType(null)
                        ->setStockPointDeliveryInformation(null)
                        ->setSpecifiedWarehouseId(null);

                    try {
                        $mainProfile->save();
                    } catch (\Exception $e) {
                        $connection->rollBack();
                        $this->messageManager->addError(__('We can not disengage this subscription main profile now.'));
                        return $result;
                    }
                }
            }
            if ($isStockPoint) {
                $mainProfileId = $this->_profileHelper->getMainFromTmpProfile($profileId);

                $resultApi = $this->buildStockPointPostData->removeFromBucket($mainProfileId);
                if (isset($resultApi['success']) && !$resultApi['success']) {
                    $connection->rollBack();
                    $this->messageManager->addError(
                        __('There are something wrong in the system. Please re-try again.')
                    );
                    return $result;
                }
            }
            $this->profileCacheRepository->removeCache($profileId);
            $connection->commit();
            $this->messageManager->addSuccess(__('The subscription profile has been disengage.'));
        } else {
            $this->messageManager->addError(__('The subscription profile does not exit.'));
        }

        return $result;
    }

    /**
     * @return mixed
     */
    protected function _initProfile()
    {
        $id = $this->getRequest()->getParam('id', 0);

        if ($id) {
            $profile = $this->_profileFactory->create()->load($id);

            if ($profile->getId()) {
                return $profile;
            }
        }

        return null;
    }

    /**
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionProfileDisengagement::profile_disengage');
    }
}
