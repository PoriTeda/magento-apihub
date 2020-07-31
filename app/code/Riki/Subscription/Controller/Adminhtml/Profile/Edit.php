<?php

namespace Riki\Subscription\Controller\Adminhtml\Profile;

use Magento\Framework\Exception\NoSuchEntityException;
use Riki\Subscription\Model\Constant;
use Riki\Subscription\Model\Config\Source\Profile\Status as ProfileStatus;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;

class Edit extends \Magento\Backend\App\Action
{
    const ADMINHTML_EDIT_PROFILE_FLAG = 'is_edit_profile';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Riki\CatalogRule\Helper\Data
     */
    protected $catalogRuleHelper;
    /**
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    protected $buildStockPointPostData;
    /**
     * @var \Riki\StockPoint\Logger\StockPointLogger
     */
    protected $stockPointLogger;
    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileData;
    /**
     * @var \Riki\Subscription\Helper\StockPoint\Data
     */
    protected $stockPointHelperData;

    protected $profileCache;

    /**
     * Edit constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\CatalogRule\Helper\Data $catalogRuleHelper
     * @param \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
     * @param \Riki\StockPoint\Logger\StockPointLogger $stockPointLogger
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Riki\Subscription\Helper\Profile\Data $profileData
     * @param \Riki\Subscription\Helper\StockPoint\Data $stockPointHelperData
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCache
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Psr\Log\LoggerInterface $logger,
        \Riki\CatalogRule\Helper\Data $catalogRuleHelper,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Riki\StockPoint\Logger\StockPointLogger $stockPointLogger,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Riki\Subscription\Helper\StockPoint\Data $stockPointHelperData,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCache
    ) {
        $this->registry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        $this->catalogRuleHelper = $catalogRuleHelper;
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->stockPointLogger = $stockPointLogger;
        $this->profileRepository = $profileRepository;
        $this->profileData = $profileData;
        $this->stockPointHelperData = $stockPointHelperData;
        $this->profileCache = $profileCache;
        parent::__construct($context);
    }

    /**
     * @return string
     */
    protected function getStrRedirectWhenFailPath()
    {
        return 'admin/admin';
    }

    /**
     * @return \Magento\Framework\View\Result\PageFactory
     */
    protected function getResultPageFactory()
    {
        return $this->resultPageFactory;
    }

    /**
     * @param string $path
     * @param array $arguments
     * @return \Magento\Framework\App\ResponseInterface
     */
    protected function _redirect($path, $arguments = [])
    {
        $this->_redirect->redirect($this->getResponse(), $path, $arguments);
        return $this->getResponse();
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Serializer_Exception
     */
    public function execute()
    {
        if (!$this->getRequest()->getParam('id')) {
            return $this->_redirect('admin/admin');
        }

        /*profile id*/
        $profileId = $this->getRequest()->getParam('id');

        /*reject tmp profile*/
        if ($this->profileData->isTmpProfileId($profileId)) {
            return $this->_redirect('admin/admin');
        }

        /*if profile have tmp profile, will be get data and edit on tmp profile*/
        if ($this->profileData->getTmpProfile($profileId) !== false) {
            $profileId = $this->profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }

        /*flag to force reset all changes of profile*/
        $isList = $this->getRequest()->getParam('list');


        /** @var \Riki\Subscription\Model\Profile\Profile $objProfile */
        $objProfile  = $this->profileData->load($profileId); /*profile data from session*/

        /** @var \Riki\Subscription\Model\Data\ApiProfile $profileDataModel */
        $profileDataModel = $this->getProfileById($profileId); /*profile data from db*/


        if (!$profileDataModel) {
            return $this->_redirect($this->getStrRedirectWhenFailPath());
        }

        $this->correctHanpukaiSubscriptionProfileStatus($objProfile, $profileDataModel);

        /** used cache instance of session */
        $reset = $isList ? true : false;
        $profileCacheWrapper = $this->profileCache->initProfile($profileId, $reset, $objProfile);

        if (!$profileCacheWrapper) {
            $this->messageManager->addError(__('There are something wrong in the system. Please re-try again'));
            return $this->_redirect('admin/admin');
        }

        $profileCache = $profileCacheWrapper->getProfileData()[$profileId];

        if (!$profileCache) {
            return $this->_redirect($this->getStrRedirectWhenFailPath());
        }

        if (isset($profileCache[Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED])
            && $profileCache[Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED] == true
        ) {
            $this->messageManager->addWarning(__('Must apply all changes to save your profile'));
        }

        $productIds = [] ;
        $productRateStockPoint = [];
        foreach ($objProfile->getProductCartData() as $productCart) {
            $productIds[] = $productCart->getData('product_id');
            $objDeliveryDate = $productCart->getData("delivery_date");
            $objDeliveryTimeSlot = $productCart->getData("delivery_time_slot");
            if ($profileCache["stock_point_profile_bucket_id"]) {
                $productRateStockPoint[] = $productCart->getData("stock_point_discount_rate");
                $originalDeliveryDate[] = $productCart->getData('original_delivery_date');
                $originalDeliveryTimeSlot[] = $productCart->getData('original_delivery_time_slot');
            }
        }
        /** set delivery date to product new */
        foreach ($profileCache["product_cart"] as $product) {
            if ($product["delivery_date"]) {
                $deliveryDateItem = $product["delivery_date"];
                $deliveryDateTimeSlot = $product["delivery_time_slot"];
            } else {
                $notDelivery = true;
                break;
            }
        }

        if (isset($notDelivery)) {
            if (!isset($deliveryDateItem)) {
                $deliveryDateItem = $objDeliveryDate;
                $deliveryDateTimeSlot = $objDeliveryTimeSlot;
            }
            foreach ($profileCache["product_cart"] as $product) {
                $product->setData("delivery_date", $deliveryDateItem);
                $product->setData("delivery_time_slot", $deliveryDateTimeSlot);
            }
        }
        /** End set delivery date to product new */

        /**  Update rate discount of stock point for products add again  */
        if ($profileCache["stock_point_profile_bucket_id"]) {
            foreach ($profileCache["product_cart"] as $product) {
                $product->setData("stock_point_discount_rate", max($productRateStockPoint));
                $product->setData('original_delivery_date', max($originalDeliveryDate));
                $product->setData('original_delivery_time_slot', max($originalDeliveryTimeSlot));
            }
        }

        /*improve performance by decrease load catalog rule*/
        $this->catalogRuleHelper->registerPreLoadedProductIds($productIds);

        /*apply stock point data for current profile data*/
        $rs = $this->_processStockPoint($profileCache, $profileId);

        /*applied stock point data failed*/
        if (!$rs) {
            return $this->_redirect($this->getStrRedirectWhenFailPath());
        }

        if(!is_null($this->registry->registry('subscription_profile'))){
            $this->registry->unregister('subscription_profile');
        }
        $this->registry->register('subscription_profile', $profileCache);
        if(!is_null($this->registry->registry('subscription_profile_obj'))){
            $this->registry->unregister('subscription_profile_obj');
        }
        $this->registry->register('subscription_profile_obj', $objProfile);
        if(!is_null($this->registry->registry('subscription_profile_data'))){
            $this->registry->unregister('subscription_profile_data');
        }
        $this->registry->register('subscription_profile_data', $profileDataModel);
        $this->registry->register(self::ADMINHTML_EDIT_PROFILE_FLAG, true);

        $courseId = $objProfile->hasData('course_id') ? $objProfile->getData('course_id') : 0;
        $frequencyId = $objProfile->getSubProfileFrequencyID();

        if(!is_null($this->registry->registry(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID))){
            $this->registry->unregister(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID);
        }
        $this->registry->register(Constant::RIKI_COURSE_ID, $courseId);
        if(!is_null($this->registry->registry(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID))){
            $this->registry->unregister(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID);
        }
        $this->registry->register(Constant::RIKI_FREQUENCY_ID, $frequencyId);

        if (!$frequencyId) {
            $this->messageManager->addError(
                __('Profile frequency is invalid.')
            );
        }

        $objResultPageFactory = $this->getResultPageFactory();
        $resultPage = $objResultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Edit Subscription Profile'));
        //[NED-5308] I dont think it is neccessary but the flow is wide - may have issue-can be uncommented!
        //$this->profileCache->save($profileCache);

        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::profile_edit');
    }

    /**
     * Process stock point
     *
     * @param $objSession
     * @param $profileId
     * @return mixed
     */
    protected function _processStockPoint($profileCache, $profileId)
    {
        /**
         * do not need to process if not exist this param
         *      reqdata is a param which is responded from stock point system
         */
        if (!$this->getRequest()->getParam('reqdata')) {
            return $profileCache;
        }

        try {
            /** save stock point if not exist and return Stock Point Id*/
            $dataRequest = $this->getRequest()->getParams();
            $this->buildStockPointPostData->checkDataNotifyMapSelected(
                $dataRequest,
                $profileCache
            );
            $stockPointId = $this->buildStockPointPostData->getRikiStockId();

            /** change data profile when has stock point data post */
            $isRequestStockPoint = $this->buildStockPointPostData->isRequestStockPointNotify();
            $isVerifyStockPoint  = $this->buildStockPointPostData->isVerifyPublicKeySuccess();
            $isVerifySectime  = $this->buildStockPointPostData->verifySecTime();
            if ($isRequestStockPoint) {
                if (!$isVerifyStockPoint) {
                    /**
                     * Stock point
                     * If SIG_VALUE is NOT matching with Magento value, then the system will show error message.
                     */
                    $this->messageManager->addError(__('There are something wrong in the system. Please re-try again'));
                    $this->stockPointLogger->info(
                        "SIG_VALUE is NOT matching with Magento value - Profile Id :" . $profileId,
                        ["type" => "is_active_notify_data_show_map"]
                    );
                    return $profileCache;
                }
                if (!isset($this->buildStockPointPostData->getDataNotifyConvert()["magento_data"]["profile_id"])) {
                    $this->messageManager->addError(__('There are something wrong in the system. Please re-try again'));
                    $this->stockPointLogger->info(
                        "Profile id form post data is NOT matching - Profile Id : " . $profileId,
                        ["type" => "is_active_notify_data_show_map"]
                    );
                    return $profileCache;
                }

                if (!$isVerifySectime) {
                    $this->messageManager->addError(__('The session has been timeout. Please re-try again.'));
                    return $profileCache;
                }
            }
            if ($isRequestStockPoint && $stockPointId) {
                /** remove flag remove when add stock point */
                $profileCache["is_delete_stock_point"] = false;
                $profileCache = $this->buildStockPointPostData->setDataStockPointToProfile($profileCache, $profileId);
                $profileCache["riki_stock_point_id"] = $stockPointId;
                $this->profileCache->save($profileCache);
            }

            return $profileCache;
        } catch (\Exception $e) {
            $this->messageManager->addError(__('Process stock point fail.'));
            $this->logger->info($e->getMessage());
        }

        return false;
    }

    /**
     * get profile by id
     *
     * @param $profileId
     * @return bool|\Riki\Subscription\Api\Data\ApiProfileInterface
     */
    protected function getProfileById($profileId)
    {
        /** @var \Riki\Subscription\Model\Data\ApiProfile $profileDataModel */
        try {
            return $this->profileRepository->get($profileId);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addError(__('This subscription profile do not exists.'));
            $this->logger->info($e->getMessage());
        }

        return false;
    }

    /**
     * Correct hanpukai subscription profile status
     * @param $objProfile
     * @param $profileDataModel
     * @return void
     */
    protected function correctHanpukaiSubscriptionProfileStatus($objProfile, $profileDataModel)
    {
        if ($courseData = $objProfile->getCourseData()) {
            if (!empty($courseData) && $objProfile->getData('status') < ProfileStatus::COMPLETED) {
                if ($courseData['subscription_type'] == CourseType::TYPE_HANPUKAI) {
                    $hanpukaiLimit = $courseData['hanpukai_maximum_order_times'];
                    if ($hanpukaiLimit && $hanpukaiLimit <= $objProfile->getOrderTimes()) {
                        $this->completeHanpukaiProfile($objProfile->getId());
                        $objProfile->setData('status', ProfileStatus::COMPLETED);
                        $profileDataModel->setData('status', ProfileStatus::COMPLETED);
                    }
                }
            }
        }
    }

    /**
     * Change profile status to complete
     * @param $entityId
     * @return void
     */
    protected function completeHanpukaiProfile($entityId)
    {
        $subscriptionProfileTable = $this->connection->getTableName('subscription_profile');
        try {
            $this->connection->beginTransaction();
            $this->connection->update(
                $subscriptionProfileTable,
                ['status' => ProfileStatus::COMPLETED],
                ['profile_id = ?' => $entityId]
            );
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->logger->info($e);
            $this->connection->rollBack();
        }
    }
}
