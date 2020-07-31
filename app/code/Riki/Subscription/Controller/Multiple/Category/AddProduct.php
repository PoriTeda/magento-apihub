<?php

namespace Riki\Subscription\Controller\Multiple\Category;

use Riki\Subscription\Model\Constant;

class AddProduct extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileData;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

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
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subOrderHelper;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $sessionManager;

    protected $multipleCategoryCache;

    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $simulator;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * AddProduct constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Riki\Subscription\Helper\Profile\Data $profileData
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\CreateProductAttributes\Model\Product\CaseDisplay $caseDisplay
     * @param \Riki\Subscription\Helper\Profile\AddSpotHelper $addSpotHelper
     * @param \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper
     * @param \Riki\Subscription\Helper\Order $subOrderHelper
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Riki\Subscription\Model\Multiple\Category\Cache $multipleCategoryCache
     * @param \Riki\Subscription\Helper\Order\Simulator $simulator
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Magento\Framework\Registry $registry,
        \Riki\CreateProductAttributes\Model\Product\CaseDisplay $caseDisplay,
        \Riki\Subscription\Helper\Profile\AddSpotHelper $addSpotHelper,
        \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper,
        \Riki\Subscription\Helper\Order $subOrderHelper,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Riki\Subscription\Model\Multiple\Category\Cache $multipleCategoryCache,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator,
        \Magento\Checkout\Model\Session $_checkoutSession
    ) {
        $this->profileData = $profileData;
        $this->customerSession = $customerSession;
        $this->profileFactory = $profileFactory;
        $this->registry = $registry;
        $this->caseDisplay = $caseDisplay;
        $this->addSpotHelper = $addSpotHelper;
        $this->campaignHelper = $campaignHelper;
        $this->subOrderHelper = $subOrderHelper;
        $this->formKeyValidator = $formKeyValidator;
        $this->sessionManager = $sessionManager;
        $this->multipleCategoryCache = $multipleCategoryCache;
        $this->simulator = $simulator;
        $this->subscriptionValidator = $subscriptionValidator;
        $this->_checkoutSession = $_checkoutSession;
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

        // Check form key
        if (!$this->formKeyValidator->validate($this->getRequest()) || !$this->getRequest()->isPost()) {
            $this->messageManager->addError(__('Form key is not valid.'));
            return $resultRedirect->setPath($this->_redirect->getRefererUrl());
        }

        // Check customer login
        if (!$this->customerSession->isLoggedIn()) {
            return $resultRedirect->setPath('customer/account/login');
        }

        // Load data form post
        $data = $this->getRequest()->getParams();
        $customerId = $this->customerSession->getCustomerId();
        $profileId = $this->getRequest()->getPost('riki_profile_id');
        $campaignId = $this->getRequest()->getPost('campaign_id');

        // clear quote
        $quote = $this->_checkoutSession->getQuote();
        foreach($quote->getAllItems() as $quoteItem){
            foreach($data['data']['product'] as $item){
                if($quoteItem->getData('product_id') == $item['product_id']){
                    $quote->deleteItem($quoteItem);
                    break;
                }
            }
        }

        // Check main profile id
        if ($this->profileData->isTmpProfileId($profileId)) {
            $this->messageManager->addErrorMessage(__('Profile ID %1 is not main profile', $profileId));
            return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        }

        // Check profile exist on current customer
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->profileFactory->create()->load($profileId);
        if (!$profile->getId()) {
            $this->messageManager->addErrorMessage(__('Profile ID %1 no longer exists', $profileId));
            return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        }

        $validate = $this->profileData->checkProfileBelongToCustomer($profileId, $customerId);
        if (!$validate) {
            $this->messageManager->addErrorMessage(__('Profile Id %1 not belong to your account'), $profileId);
            return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        }

        // Validate product
        $postProductData = $this->campaignHelper->mergeProductWithSameId($data);
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
                return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            } else {
                if (empty($arrProduct)) {
                    $message = sprintf(
                        __('There are no spot product added to profile ID #%s, please choose an item from grid'),
                        $profileId
                    );
                    $this->messageManager->addErrorMessage($message);
                    return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
                }
            }

            // Set registry to apply catalogrule subscription
            $this->registry->register(Constant::RIKI_COURSE_ID, $profile->getCourseId());
            $this->registry->register(Constant::RIKI_FREQUENCY_ID, $profile->getSubProfileFrequencyID());
            $this->registry->register('subscription_profile_obj', $profile);

            // Check minimum amount restriction
            $objectDataSimulate = $this->campaignHelper->prepareSimulator($profile, $arrProduct);
            $simulatedOrder= $this->simulator->createSimulatorOrderHasData($objectDataSimulate);
            $subCourse = $this->campaignHelper->loadCourse($profile->getCourseId());
            $validateResults = $this->subOrderHelper->validateMinimumAmountRestriction(
                $simulatedOrder,
                $subCourse,
                $profile
            );
            if (!$validateResults['status']) {
                $this->messageManager->addErrorMessage(__($validateResults['message']));
                return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            }

            /** Validate maximum qty restriction */
            $prepareData = $this->profileData->prepareDataForValidateMaximumQty(
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
                return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            }

            /** save simulator for cache */
            if ($this->sessionManager->getData('multiple_category_cache_id')) {
                $identifier = $this->sessionManager->getData('multiple_category_cache_id');
            } else {
                $identifier = $this->multipleCategoryCache->getCacheIdentifier();
                $this->sessionManager->setData('multiple_category_cache_id', $identifier);
            }
            $this->multipleCategoryCache->saveCache($simulatedOrder, $identifier);

            // Process data add spot product
            $this->processDataAddMultipleSpotProduct($arrProduct, $profileId, $campaignId);
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t add this item to your profile right now.'));
            return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        }

        return $resultRedirect->setPath('subscriptions/multiple_category/confirm');
    }

    /**
     * Process data add spot product
     *
     * @param array $arrProduct
     * @param $profileId
     * @param $campaignId
     */
    protected function processDataAddMultipleSpotProduct($arrProduct, $profileId, $campaignId)
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

        $multipleCategoryData = [
            'profile_id' => $profileId,
            'new_spot_products' => $newSpotProducts,
            'campaign_id' => $campaignId
        ];

        // Store data to session
        $this->sessionManager->setData('multiple_category_data', $multipleCategoryData);
    }
}
