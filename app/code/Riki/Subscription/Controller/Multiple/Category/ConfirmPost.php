<?php

namespace Riki\Subscription\Controller\Multiple\Category;

use Riki\Subscription\Model\Constant;

class ConfirmPost extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    protected $buildStockPointPostData;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;

    /**
     * @var \Riki\Framework\Helper\Transaction\Database
     */
    protected $dbTransaction;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subOrderHelper;

    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $simulator;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\Subscription\Helper\Profile\CampaignHelper
     */
    protected $campaignHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Riki\Subscription\Helper\Indexer\Data
     */
    protected $profileIndexerHelper;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * ConfirmPost constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Riki\Subscription\Helper\Profile\Data $profileHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     * @param \Riki\Framework\Helper\Transaction\Database $dbTransaction
     * @param \Riki\Subscription\Helper\Order $subOrderHelper
     * @param \Riki\Subscription\Helper\Order\Simulator $simulator
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Subscription\Helper\Indexer\Data $profileIndexerHelper
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Riki\Framework\Helper\Transaction\Database $dbTransaction,
        \Riki\Subscription\Helper\Order $subOrderHelper,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper,
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Helper\Indexer\Data $profileIndexerHelper,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
    ) {
        $this->formKeyValidator = $formKeyValidator;
        $this->profileHelper = $profileHelper;
        $this->customerSession = $customerSession;
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->dbTransaction = $dbTransaction;
        $this->subOrderHelper = $subOrderHelper;
        $this->simulator = $simulator;
        $this->sessionManager = $sessionManager;
        $this->logger = $logger;
        $this->campaignHelper = $campaignHelper;
        $this->registry = $registry;
        $this->profileIndexerHelper = $profileIndexerHelper;
        $this->subscriptionValidator = $subscriptionValidator;
        parent::__construct($context);
    }

    /**
     * Save multiple category action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throw \Exception
     */
    public function execute()
    {
        $redirectUrl = null;
        $resultRedirect = $this->resultRedirectFactory->create();

        // Check customer login
        if (!$this->customerSession->isLoggedIn()) {
            return $resultRedirect->setPath('customer/account/login');
        }

        // Check form key
        if (!$this->formKeyValidator->validate($this->getRequest()) || !$this->getRequest()->isPost()) {
            $this->messageManager->addErrorMessage(__('Form key is not valid.'));
            return $resultRedirect->setPath('customer/account/');
        }

        // Load data form post
        $postData = $this->getRequest()->getParams();
        $arrSpotProductSubmit = $postData['product'];
        $customerId = $this->customerSession->getCustomerId();
        $profileId = $postData['profile_id'];
        $campaignId = $postData['campaign_id'];

        // Check main profile id
        if ($this->profileHelper->isTmpProfileId($profileId)) {
            $this->messageManager->addErrorMessage(__('Profile ID %1 is not main profile', $profileId));
            return $resultRedirect->setPath('subscriptions/multiple_category/view', ['id' => $campaignId]);
        }

        // Check profile exist on current customer
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->profileHelper->loadProfileModel($profileId);
        if (!$profile->getId()) {
            $this->messageManager->addErrorMessage(__('Profile ID %1 no longer exists', $profileId));
            return $resultRedirect->setPath('subscriptions/multiple_category/view', ['id' => $campaignId]);
        }
        $validate = $this->profileHelper->checkProfileBelongToCustomer($profileId, $customerId);
        if (!$validate) {
            $this->messageManager->addErrorMessage(__('Profile Id %1 not belong to your account'), $profileId);
            return $resultRedirect->setPath('subscriptions/multiple_category/view', ['id' => $campaignId]);
        }

        $addedProducts = [];

        try {
            // Validate product
            $errorCount = 0;
            $isProfileStockPoint = $this->campaignHelper->isProfileStockPoint($profile);

            // Get product collection
            $productCollection = $this->campaignHelper->getProductCollectionByIds(array_keys($arrSpotProductSubmit));
            foreach ($productCollection as $id => $product) {
                // Check new product exist in profile
                $validateAddSpot = $this->campaignHelper->validateSpotProductIsExistInProfile($profile, $id);
                if ($validateAddSpot == Constant::ADD_SPOT_PRODUCT_ERROR_SPOT_PRODUCT_IS_EXIST_LIKE_MAIN_PRODUCT) {
                    $errorCount++;

                    $message = __(
                        '%1 is already included in the selected subscription profile. If you want to change quantity, please change it from mypage.',
                        $product->getName()
                    );
                    $this->messageManager->addErrorMessage($message);
                }

                // Check allow stock point
                if ($isProfileStockPoint) {
                    $isAllowStockPoint = $this->campaignHelper->validateProductAddedToStockPoint(
                        $profile,
                        $product,
                        $arrSpotProductSubmit[$id]['qty'],
                        true
                    );
                    if (!$isAllowStockPoint) {
                        $errorCount++;

                        $message = __('The selected product is not allowed to buy with Stock Point.');
                        $this->messageManager->addErrorMessage($message);
                    }
                }

                $addedProducts[$id] = [
                    'name'  => $product->getName(),
                    'qty'   => $arrSpotProductSubmit[$id]['qty']
                ];
            }

            if ($errorCount) {
                return $resultRedirect->setPath('subscriptions/multiple_category/view', ['id' => $campaignId]);
            }

            // Set registry to apply catalogrule subscription
            $this->registry->register(Constant::RIKI_COURSE_ID, $profile->getCourseId());
            $this->registry->register(Constant::RIKI_FREQUENCY_ID, $profile->getSubProfileFrequencyID());
            $this->registry->register('subscription_profile_obj', $profile);

            // Check minimum amount restriction
            $objectDataSimulate = $this->profileHelper->makeObjectDataForSimulate(
                $profileId,
                $postData,
                $arrSpotProductSubmit
            );
            $orderSimulate = $this->simulator->createSimulatorOrderHasData($objectDataSimulate);
            $subCourse = $this->profileHelper->getCourseData($profile->getCourseId());
            $validateResults = $this->subOrderHelper->validateMinimumAmountRestriction(
                $orderSimulate,
                $subCourse,
                $profile
            );
            if (!$validateResults['status']) {
                $this->messageManager->addErrorMessage(__($validateResults['message']));
                return $resultRedirect->setPath('subscriptions/multiple_category/view', ['id' => $campaignId]);
            }

            /** Validate maximum qty restriction */
            $prepareData = $this->profileHelper->prepareDataForValidateMaximumQty(
                $objectDataSimulate->getData('product_cart')
            );
            $validateMaximumQty = $this->subscriptionValidator->setProfileId($profileId)
                ->setProductCarts($prepareData)
                ->validateMaximumQtyRestriction();
            if ($validateMaximumQty['error'] && !empty($validateMaximumQty['product_errors'])) {
                $message = $this->subscriptionValidator->getMessageMaximumError(
                    $validateMaximumQty['product_errors'],
                    $validateMaximumQty['maxQty']
                );
                $this->messageManager->addErrorMessage($message);
                return $resultRedirect->setPath('subscriptions/multiple_category/view', ['id' => $campaignId]);
            }

            // Save spot product to profile
            $this->dbTransaction->beginTransaction();
            $productCartData = $objectDataSimulate->getData('product_cart');
            $result = $this->processAddProductToProfile($productCartData, $postData);

            if ($result) {
                // Call api remove stock point
                if ($isProfileStockPoint) {
                    $profileCarrier = $this->validateStockPointProduct->checkProfileStockPointSubCarrier($profile);
                    $isCleanDataCarrier = $this->validateStockPointProduct->canCleanDataSpCarrier();
                    if ($profileCarrier && $isCleanDataCarrier) {
                        $result = $this->removeProfileStockPointSubCarrier($profileId, $profile);
                        if ($result) {
                            $this->dbTransaction->commit();
                        } else {
                            $this->messageManager->addErrorMessage(
                                __("There are something wrong in the system. Please re-try again.")
                            );
                            $this->dbTransaction->rollback();
                            return $resultRedirect->setPath('subscriptions/multiple_category/view', ['id' => $campaignId]);
                        }
                    } else {
                        // For stock point type different subcarrier
                        $this->dbTransaction->commit();
                    }
                } else {
                    // For profile not stock point
                    $this->dbTransaction->commit();
                }

                // Update profile simulate cache after add spot to profile success
                $this->profileIndexerHelper->updateDataProfileCache($profileId, $orderSimulate);

                // Use for success multiple category page
                $successData = [
                    'profile_id' => $profile->getId(),
                    'course_code' => $subCourse->getCourseCode(),
                    'total_amount' => $this->getTotalAmountOfAddedProducts($arrSpotProductSubmit, $orderSimulate),
                    'accessed' => false,
                    'added_products'  => $addedProducts
                ];

                $this->sessionManager->setSuccessData($successData);
                $this->sessionManager->unsMulltipleCategoryCampaignSelectedProduct();
                return $resultRedirect->setPath('subscriptions/multiple_category/success');
            } else {
                $this->messageManager->addErrorMessage(
                    __("I'm sorry. We were unable to accept changes. Sorry to trouble you, please try again or contact Nestlé Mail Order Call Center (0120-600-868).")
                );
                return $resultRedirect->setPath('subscriptions/multiple_category/view', ['id' => $campaignId]);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->dbTransaction->rollback();
            $this->messageManager->addErrorMessage(
                __("I'm sorry. We were unable to accept changes. Sorry to trouble you, please try again or contact Nestlé Mail Order Call Center (0120-600-868).")
            );
        }

        return $resultRedirect->setPath('subscriptions/multiple_category/view', ['id' => $campaignId]);
    }

    /**
     * Process add product to profile
     *
     * @param $productCartData
     * @param $postData
     * @return mixed
     */
    public function processAddProductToProfile($productCartData, $postData)
    {
        $result = null;
        foreach ($productCartData as $keyNewProduct => $newProductCartProfileData) {
            if (strpos($keyNewProduct, 'new_product') !== false) {
                $result = $this->profileHelper->saveSpotProduct($postData, $newProductCartProfileData);
            }
        }

        return $result;
    }

    /**
     * Get total amount of added products
     *
     * @param $newProducts
     * @param $orderSimulate
     * @return mixed
     */
    public function getTotalAmountOfAddedProducts($newProducts, $orderSimulate)
    {
        $totalAmount = 0;

        if (!empty($newProducts)) {
            $orderItems = $orderSimulate->getItems();
            foreach ($orderItems as $item) {
                if (!$item->getParentItemId() && in_array($item->getData('product_id'), array_keys($newProducts))) {
                    $totalAmount += $item->getData('price_incl_tax') * $newProducts[$item->getData('product_id')]['qty'];
                }
            }
        }

        return $totalAmount;
    }

    /**
     * Remove stock point profile subcarrier
     *
     * @param $mainProfileId
     * @param $profile
     * @return bool
     */
    public function removeProfileStockPointSubCarrier($mainProfileId, $profile)
    {
        if ($profile) {
            $resultApi = $this->buildStockPointPostData->removeFromBucket($mainProfileId);
            if (isset($resultApi['success']) && $resultApi['success']) {
                $result = $this->profileHelper->cleanDataProfileStockPoint($profile);
                if ($result) {
                    return true;
                }
            }
        }
        return false;
    }
}
