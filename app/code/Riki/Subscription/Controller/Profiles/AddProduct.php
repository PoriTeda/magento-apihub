<?php


namespace Riki\Subscription\Controller\Profiles;

use Riki\Subscription\Helper\Profile\CampaignHelper;
use Riki\Subscription\Model\Constant;

class AddProduct extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileData;

    /**
     * @var \Riki\CreateProductAttributes\Model\Product\CaseDisplay
     */
    protected $caseDisplay;

    /**
     * @var \Riki\Subscription\Helper\Profile\AddSpotHelper
     */
    protected $addSpotHelper;

    /**
     * @var \Riki\Subscription\Helper\Profile\CampaignHelper
     */
    protected $campaignHelper;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $simulator;

    /**
     * @var \Riki\Subscription\Helper\Summer\Campaign\Validate
     */
    protected $validate;

    /**
     * AddProduct constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\CreateProductAttributes\Model\Product\CaseDisplay $caseDisplay
     * @param \Riki\Subscription\Helper\Profile\AddSpotHelper $addSpotHelper
     * @param \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Riki\Subscription\Helper\Order\Simulator $simulator
     * @param \Riki\Subscription\Helper\Summer\Campaign\Validate $validate
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Riki\CreateProductAttributes\Model\Product\CaseDisplay $caseDisplay,
        \Riki\Subscription\Helper\Profile\AddSpotHelper $addSpotHelper,
        \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Riki\Subscription\Helper\Summer\Campaign\Validate $validate
    )
    {
        $this->customerSession = $customerSession;
        $this->profileFactory = $profileFactory;
        $this->profileData = $profileData;
        $this->caseDisplay = $caseDisplay;
        $this->addSpotHelper = $addSpotHelper;
        $this->campaignHelper = $campaignHelper;
        $this->sessionManager = $sessionManager;
        $this->simulator = $simulator;
        $this->validate = $validate;
        parent::__construct($context);
    }

    /**
     * Confirm multiple category action
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $this->validate->setResultRedirect($resultRedirect)->setUrl($this->getReferralUrl());

        // Check form key
        if ($validateFormKey = $this->validate->validateFormKey()) {
            return $validateFormKey;
        };

        // Check customer login
        if ($validateCustomerLogin = $this->validate->validateCustomerLogin()) {
            return $validateCustomerLogin;
        }

        // Load data form post
        $reqDataValue = $this->getRequest()->getParam('reqdata');

        // Check request data
        if ($validateRequestData = $this->validate->validateRequestData($reqDataValue)) {
            return $validateRequestData;
        }

        $receiveData = $this->campaignHelper->decodeData($reqDataValue);

        $validator = $this->campaignHelper->validatePostAuthorization($receiveData);

        if ($validator['isValid'] == false) {
            $this->messageManager->addErrorMessage($validator['errorMsg']);
            return $resultRedirect->setUrl($this->getReferralUrl());
        }

        $data = $this->campaignHelper->decodeData($receiveData['data']);
        $dataProductSkus = array_filter(array_column($data['products'], 'sku'));
        $customerId = $this->customerSession->getCustomerId();
        $profileId = $this->getRequest()->getPost('profile_id');

        // clear quote
        $this->validate->clearQuote($dataProductSkus);

        // Check main profile id
        if ($checkMainProfile = $this->validate->checkMainProfile($profileId)) {
            return $checkMainProfile;
        }

        // Check profile exist on current customer
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->profileFactory->create()->load($profileId);

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

        // Validate product
        $postProductData = $this->campaignHelper->mergeProductWithSameId($data, \Riki\Subscription\Helper\Profile\CampaignHelper::SUMMER_CAMPAIGN);
        $arrProduct = [];

        $isProfileStockPoint = $this->campaignHelper->isProfileStockPoint($profile);

        try {
            $errorCount = 0;
            $profileDeliveryType = $this->addSpotHelper->getDeliveryTypeOfProfile($profileId);

            // Get product collection
            $productCollection = $this->campaignHelper->getProductCollectionByIds(array_keys($postProductData));

            foreach ($productCollection as $id => $product) {
                $productQtyRequest = (isset($postProductData[$id]['qty_case']) && $postProductData[$id]['qty_case'] > 0)
                    ? $postProductData[$id]['qty_case'] : $postProductData[$id]['qty'];
                if ($productQtyRequest > 0) {
                    $errorFlag = false;

                    // Check product availability
                    if (!$product) {
                        $errorFlag = true;
                        $errorCount++;

                        $message = sprintf(
                            __('The product %s added no longer exists'),
                            $product->getName()
                        );
                        $this->messageManager->addErrorMessage($message);
                    }

                    // Check delivery type of product
                    if ($profileDeliveryType) {
                        $productDeliveryType = $product->getData('delivery_type');
                        if (!in_array($productDeliveryType, $profileDeliveryType)) {
                            $errorFlag = true;
                            $errorCount++;

                            $message = sprintf(
                                __('The subscription just allow to add products in the same delivery type')
                            );
                            $this->messageManager->addErrorMessage($message);
                        }
                    }

                    // Check new product exist in profile
                    $validateAddSpot = $this->campaignHelper->validateSpotProductIsExistInProfile($profile, $id);
                    if ($validateAddSpot == Constant::ADD_SPOT_PRODUCT_ERROR_SPOT_PRODUCT_IS_EXIST_LIKE_MAIN_PRODUCT) {
                        $errorFlag = true;
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
                            $productQtyRequest
                        );
                        if (!$isAllowStockPoint) {
                            $errorFlag = true;
                            $errorCount++;

                            $message = sprintf(
                                __('The selected product is not allowed to buy with Stock Point.')
                            );
                            $this->messageManager->addErrorMessage($message);
                        }
                    }

                    if (!$errorFlag) {
                        $arrProduct[$id]['product'] = $product;
                        $arrProduct[$id]['qty_request'] = $productQtyRequest;
                    }
                }
            }

            if ($errorCount) {
                return $resultRedirect->setUrl($this->getReferralUrl());
            } else {
                if (empty($arrProduct)) {
                    $message = __('There are no spot product added to profile ID #%1, please choose an item from grid', $profileId);
                    $this->messageManager->addErrorMessage($message);
                    return $resultRedirect->setUrl($this->getReferralUrl());
                }
            }

            // Set registry to apply catalogrule subscription
            $this->validate->setRegistryToApplyCatalogRule($profile);

            // Check minimum amount restriction
            $objectDataSimulate = $this->campaignHelper->prepareSimulator($profile, $arrProduct);
            $simulatedOrder = $this->simulator->createSimulatorOrderHasData($objectDataSimulate);

            $prepareData = $this->profileData->prepareDataForValidateMaximumQty(
                $objectDataSimulate->getData('product_cart')
            );

            if ($validateMinimumAmount = $this->validate->validateMinimumAmount($simulatedOrder, $profile, $subCourse)) {
                return $validateMinimumAmount;
            }

            // Validate maximum qty restriction
            if ($validateMaximumQty = $this->validate->validateMaximumQty($prepareData, $profileId)) {
                return $validateMaximumQty;
            }

            // Validate minimum order qty
            if ($validateMinimumOrderQty = $this->validate->validateMinimumOrderQty($prepareData, $subCourse, $profile)) {
                return $validateMinimumOrderQty;
            }

            // Save simulator for cache
            $this->validate->saveSimulatorForCache($simulatedOrder);

            // Process data add spot product
            $this->processDataAddMultipleProduct($arrProduct, $profileId, $reqDataValue);
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t add this item to your profile right now.'));
            return $resultRedirect->setUrl($this->getReferralUrl());
        }

        return $resultRedirect->setPath('subscriptions/profiles/confirm');
    }

    public function getReferralUrl()
    {
        $backUrl = $this->_redirect->getRefererUrl();

        if (strpos($backUrl, 'reqdata') === false && $this->getRequest()->getPost('reqdata')) {
            $queryString = http_build_query([
                'reqdata' => $this->getRequest()->getPost('reqdata')
            ]);

            $backUrl .= '?' . $queryString;
        }

        return $backUrl;
    }


    /**
     * Process data add spot product
     *
     * @param array $arrProduct
     * @param $profileId
     * @param $reqDataValue
     */
    protected function processDataAddMultipleProduct($arrProduct, $profileId, $reqDataValue)
    {
        $newSpotProducts = [];
        foreach ($arrProduct as $item) {
            $product = $item['product'];
            $productQty = $item['qty_request'];

            $unitQty = $product->getData('unit_qty');
            $unitDisplay = $product->getData('case_display');
            $pieceQty = $this->caseDisplay->getQtyPieceCaseForSaving($unitDisplay, $unitQty, $productQty);
            $product->setPieceQty($pieceQty);
            $product->setQty($pieceQty);

            $newSpotProducts[$product->getId()] = $pieceQty;
        }

        $summerCampaignData = [
            'profile_id' => $profileId,
            'new_spot_products' => $newSpotProducts,
            'reqdata' => $reqDataValue
        ];

        // Store data to session
        $this->sessionManager->setData(CampaignHelper::SUMMER_CAMPAIGN_DATA, $summerCampaignData);
    }
}