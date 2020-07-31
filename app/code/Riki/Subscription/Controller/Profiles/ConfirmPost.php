<?php


namespace Riki\Subscription\Controller\Profiles;

use Riki\Subscription\Model\Constant;

class ConfirmPost extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;

    /**
     * @var \Riki\Framework\Helper\Transaction\Database
     */
    protected $dbTransaction;

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
     * @var \Riki\Subscription\Helper\Indexer\Data
     */
    protected $profileIndexerHelper;

    /**
     * @var \Riki\Subscription\Controller\Multiple\Category\ConfirmPost
     */
    protected $confirmPost;

    /**
     * @var \Riki\Subscription\Helper\Summer\Campaign\Validate
     */
    protected $validate;

    /**
     * ConfirmPost constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Riki\Subscription\Helper\Profile\Data $profileHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     * @param \Riki\Framework\Helper\Transaction\Database $dbTransaction
     * @param \Riki\Subscription\Helper\Order\Simulator $simulator
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper
     * @param \Riki\Subscription\Helper\Indexer\Data $profileIndexerHelper
     * @param \Riki\Subscription\Controller\Multiple\Category\ConfirmPost $confirmPost
     * @param \Riki\Subscription\Helper\Summer\Campaign\Validate $validate
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Riki\Framework\Helper\Transaction\Database $dbTransaction,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper,
        \Riki\Subscription\Helper\Indexer\Data $profileIndexerHelper,
        \Riki\Subscription\Controller\Multiple\Category\ConfirmPost $confirmPost,
        \Riki\Subscription\Helper\Summer\Campaign\Validate $validate
    )
    {
        $this->profileHelper = $profileHelper;
        $this->customerSession = $customerSession;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->dbTransaction = $dbTransaction;
        $this->simulator = $simulator;
        $this->sessionManager = $sessionManager;
        $this->logger = $logger;
        $this->campaignHelper = $campaignHelper;
        $this->profileIndexerHelper = $profileIndexerHelper;
        $this->confirmPost = $confirmPost;
        $this->validate = $validate;
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

        // Load data form post
        $postData = $this->getRequest()->getParams();

        $arrSpotProductSubmit = $postData['product'];
        $customerId = $this->customerSession->getCustomerId();
        $profileId = $postData['profile_id'];
        $reqData = $postData['reqdata'];
        $receiveData = $this->campaignHelper->decodeData($reqData);
        $data = $this->campaignHelper->decodeData($receiveData['data']);
        $dataProductSkus = array_filter(array_column($data['products'], 'sku'));

        $url = $this->_url->getUrl('subscriptions/profiles/select', ['reqdata' => $reqData]);
        $this->validate->setResultRedirect($resultRedirect)->setUrl($url);


        // Check form key
        if ($validateFormKey = $this->validate->validateFormKey()) {
            return $validateFormKey;
        };

        // Check customer login
        if ($validateCustomerLogin = $this->validate->validateCustomerLogin()) {
            return $validateCustomerLogin;
        }

        // Check request data
        if ($validateRequestData = $this->validate->validateRequestData($reqData)) {
            return $validateRequestData;
        }

        $validator = $this->campaignHelper->validatePostAuthorization($receiveData);

        if ($validator['isValid'] == false) {
            $this->messageManager->addErrorMessage($validator['errorMsg']);
            return $resultRedirect->setPath('subscriptions/profiles/select', ['reqdata' => $reqData]);
        }

        // Check main profile id
        if ($checkMainProfile = $this->validate->checkMainProfile($profileId)) {
            return $checkMainProfile;
        }

        // Check profile exist on current customer
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->profileHelper->loadProfileModel($profileId);

        if ($checkProfileExistOnCustomer = $this->validate->checkProfileExistOnCustomer($profile, $profileId)) {
            return $checkProfileExistOnCustomer;
        }

        // Check product exist in product list of course
        $subCourse = $this->campaignHelper->loadCourse($profile->getCourseId());

        if ($checkProductExistInProductListOfCourse = $this->validate->checkProductExistInProductListOfCourse($profile, $dataProductSkus, $subCourse)) {
            return $checkProductExistInProductListOfCourse;
        }

        // Check profile belong to customer
        if ($checkProfileBelongToCustomer = $this->validate->checkProfileBelongToCustomer($profileId, $customerId)) {
            return $checkProfileBelongToCustomer;
        }

        // Check product credit card only exist in profile have payment method != paygent
        if ($checkCcProductIsInNotCcProfile = $this->validate->checkCcProductIsInNotCcProfile($profile, $dataProductSkus, $subCourse)) {
            return $checkCcProductIsInNotCcProfile;
        }

        // Check product is out of stock
        if ($checkProductOutOfStock = $this->validate->checkProductOutOfStock($dataProductSkus, $profile)) {
            return $checkProductOutOfStock;
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
                    'name' => $product->getName(),
                    'qty' => $arrSpotProductSubmit[$id]['qty']
                ];
            }

            if ($errorCount) {
                return $resultRedirect->setPath('subscriptions/profiles/select', ['reqdata' => $reqData]);
            }

            // Set registry to apply catalogrule subscription
            $this->validate->setRegistryToApplyCatalogRule($profile);

            // Check minimum amount restriction
            $objectDataSimulate = $this->profileHelper->makeObjectDataForSimulate(
                $profileId,
                $postData,
                $arrSpotProductSubmit
            );
            $orderSimulate = $this->simulator->createSimulatorOrderHasData($objectDataSimulate);

            $prepareData = $this->profileHelper->prepareDataForValidateMaximumQty(
                $objectDataSimulate->getData('product_cart')
            );

            $this->validate->validateMinimumAmount($orderSimulate, $profile, $subCourse);


            // Validate maximum qty restriction
            if ($validateMaximumQty = $this->validate->validateMaximumQty($prepareData, $profileId)) {
                return $validateMaximumQty;
            }

            // Validate minimum order qty
            if ($validateMinimumOrderQty = $this->validate->validateMinimumOrderQty($prepareData, $subCourse, $profile)) {
                return $validateMinimumOrderQty;
            }

            // Save product to profile
            $this->dbTransaction->beginTransaction();
            $productCartData = $objectDataSimulate->getData('product_cart');
            $result = $this->processAddProductToProfile($productCartData, $postData);

            if ($result) {
                // Call api remove stock point
                if ($isProfileStockPoint) {
                    $profileCarrier = $this->validateStockPointProduct->checkProfileStockPointSubCarrier($profile);
                    $isCleanDataCarrier = $this->validateStockPointProduct->canCleanDataSpCarrier();
                    if ($profileCarrier && $isCleanDataCarrier) {
                        $result = $this->confirmPost->removeProfileStockPointSubCarrier($profileId, $profile);
                        if ($result) {
                            $this->dbTransaction->commit();
                        } else {
                            $this->messageManager->addErrorMessage(
                                __("There are something wrong in the system. Please re-try again.")
                            );
                            $this->dbTransaction->rollback();
                            return $resultRedirect->setPath('subscriptions/profiles/select', ['reqdata' => $reqData]);
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
                    'total_amount' => $this->confirmPost->getTotalAmountOfAddedProducts($arrSpotProductSubmit, $orderSimulate),
                    'accessed' => false,
                    'added_products' => $addedProducts
                ];

                $this->sessionManager->setSuccessData($successData);
                return $resultRedirect->setPath('subscriptions/profiles/success');
            } else {
                $this->messageManager->addErrorMessage(
                    __("I'm sorry. We were unable to accept changes. Sorry to trouble you, please try again or contact Nestlé Mail Order Call Center (0120-600-868).")
                );
                return $resultRedirect->setPath('subscriptions/profiles/select', ['reqdata' => $reqData]);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->dbTransaction->rollback();
            $this->messageManager->addErrorMessage(
                __("I'm sorry. We were unable to accept changes. Sorry to trouble you, please try again or contact Nestlé Mail Order Call Center (0120-600-868).")
            );
        }

        return $resultRedirect->setPath('subscriptions/profiles/select', ['reqdata' => $reqData]);
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
                $result = $this->profileHelper->saveMainProduct($postData, $newProductCartProfileData);
            }
        }

        return $result;
    }
}