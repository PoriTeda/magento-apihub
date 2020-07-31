<?php

namespace Riki\Subscription\Controller\Adminhtml\Profile;

class ChangeWarehouse extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileDataHelper;

    protected $profileCache;

    /**
     * ChangeWarehouse constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Riki\Subscription\Helper\Profile\Data $profileDataHelper
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCache
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Subscription\Helper\Profile\Data $profileDataHelper,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCache
    ) {
        parent::__construct($context);
        $this->messageManager = $context->getMessageManager();
        $this->jsonFactory = $jsonFactory;
        $this->dateTime = $dateTime;
        $this->profileDataHelper = $profileDataHelper;
        $this->profileCache = $profileCache;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        $profileId = $this->getRequest()->getParam('id');

        $warehouseId = $this->getRequest()->getParam('warehouseId');

        $tmpProfile = $this->profileDataHelper->getTmpProfile($profileId);

        if ($tmpProfile !== false) {
            /*get exactly profileId if this profile is tmp profile*/
            $profileId = $tmpProfile->getData('linked_profile_id');
        }

        /** Get profile from cache */
        $profileData = $this->profileCache->getProfileDataCache($profileId);

        if (!$profileData) {
            return $result->setData([
                'error' => true,
                'message' => __('Something went wrong, please reload page.')
            ]);
        }

        $profileData['specified_warehouse_id'] = $warehouseId;

        /*flag to check this profile has changed*/
        $profileData[\Riki\Subscription\Model\Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED] = true;

        $this->profileCache->save($profileData);

        $this->messageManager->addSuccess(__('The warehouse has been changed.'));

        return $result->setData([
            'error' => false,
            'message' => ''
        ]);
    }
}
