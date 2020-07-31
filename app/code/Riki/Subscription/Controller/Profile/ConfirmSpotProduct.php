<?php

namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Registry;
use Riki\Subscription\Model\Profile\ProfileFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Catalog\Model\ProductRepository;
use Riki\Subscription\Model\Constant;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;
use Magento\Framework\DataObject;

class ConfirmSpotProduct extends Action
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileData;

    /**
     * @var CaseDisplay
     */
    protected $caseDisplay;

    /**
     * @var \Riki\Subscription\Helper\Profile\AddSpotHelper
     */
    protected $addSpotHelper;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subscriptionOrderHelper;

    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $simulator;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * ConfirmSpotProduct constructor.
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param ProfileFactory $profileFactory
     * @param Registry $registry
     * @param PageFactory $pageFactory
     * @param ProductRepository $productRepository
     * @param CaseDisplay $caseDisplay
     * @param \Riki\Subscription\Helper\Profile\AddSpotHelper $addSpotHelper
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        Context $context,
        CustomerSession $customerSession,
        ProfileFactory $profileFactory,
        Registry $registry,
        PageFactory $pageFactory,
        ProductRepository $productRepository,
        CaseDisplay $caseDisplay,
        \Riki\Subscription\Helper\Profile\AddSpotHelper $addSpotHelper,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Riki\Subscription\Helper\Order $subscriptionOrderHelper,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
    ) {
        $this->caseDisplay = $caseDisplay;
        $this->profileData = $helperProfile;
        $this->customerSession = $customerSession;
        $this->profileFactory = $profileFactory;
        $this->registry = $registry;
        $this->resultPageFactory = $pageFactory;
        $this->productRepository = $productRepository;
        $this->addSpotHelper = $addSpotHelper;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->subscriptionOrderHelper = $subscriptionOrderHelper;
        $this->simulator = $simulator;
        $this->subscriptionValidator = $subscriptionValidator;
        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            return $this->_redirect('customer/account/login');
        }
        $customerId = $this->customerSession->getCustomerId();
        $profileId = $this->getRequest()->getPost('profile_id');
        if ($this->profileData->isTmpProfileId($profileId)) {
            return $this->_redirect('*/*');
        }
        $validate = $this->profileData->checkProfileBelongToCustomer($profileId, $customerId);
        if (!$validate) {
            return $this->_redirect('*/*');
        }
        $productId = $this->getRequest()->getPost('product_id');
        $productQty = $this->getRequest()->getPost('product_qty');

        if (!$profileId || !$productId) {
            $this->resultRedirectFactory->create()
                ->setUrl($this->_redirect->getRefererUrl());
        }

        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->profileFactory->create()->load($profileId);
        if (!$profile->getId()) {
            $this->messageManager->addErrorMessage(__('The subscription profile no longer exists'));
            return $this->resultRedirectFactory->create()
                ->setUrl($this->_redirect->getRefererUrl());
        }

        $product = $this->productRepository->getById($productId);
        if (!$product->getId()) {
            $this->messageManager->addErrorMessage(__('The product added no longer exists'));
            return $this->resultRedirectFactory->create()
                ->setUrl($this->_redirect->getRefererUrl());
        }

        $allowStockPoint = $this->checkAddSpotStockPoint($profile, $product, $productQty);
        if ($allowStockPoint) {
            return $allowStockPoint;
        }

        $isDeliveryTypeProfile = $this->checkDeliveryType($product, $profileId);
        if ($isDeliveryTypeProfile) {
            return $isDeliveryTypeProfile;
        }

        $isNewProfileExist = $this->checkNewSpotProductExistInProfile($profileId, $productId);
        if ($isNewProfileExist) {
            return $isNewProfileExist;
        }

        /** Validate maximum qty restriction */
        $validateMaximumQty = $this->checkMaximumQtyRestriction($profileId, $product, $productQty);
        if ($validateMaximumQty) {
            return $validateMaximumQty;
        }

        $this->processDataAddSpotProduct($productQty, $product, $profile);

        $message = $this->validateOrderAmount($profile, $product);
        if (!empty($message)) {
            $this->messageManager->addError($message);
            return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRefererUrl());
        }

        $this->_view->loadLayout();
        $this->_view->getLayout()->initMessages();
        return $this->resultPageFactory->create();
    }

    /**
     * Validate spot product is exits in product cart
     *
     * @param $profileId
     * @param $productAddId
     * @return int
     */
    protected function isNewSpotProductExistInProfile($profileId, $productAddId)
    {
        $error = 0;
        if ($this->profileData->getTmpProfile($profileId) !== false) {
            $profileId = $this->profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        $arrProductInProfile = $this->profileData->getArrProductCart($profileId);
        $arrProductIdOfProductCart = array_keys($arrProductInProfile);
        if (in_array($productAddId, $arrProductIdOfProductCart)) {
            if ($arrProductInProfile[$productAddId]['profile']->getData('is_spot') == 1) {
                $error = Constant::ADD_SPOT_PRODUCT_ERROR_SPORT_PRODUCT_IS_EXIST_LIKE_SPOT;
            } else {
                $error = Constant::ADD_SPOT_PRODUCT_ERROR_SPOT_PRODUCT_IS_EXIST_LIKE_MAIN_PRODUCT;
            }
        }
        return $error;
    }

    /**
     * Process data add spot product
     * @param $productQty
     * @param $product
     * @param $profile
     */
    protected function processDataAddSpotProduct($productQty, $product, $profile)
    {
        if (!$productQty) {
            $productQty = 1;
        }

        /**
         * set piece case
         */
        $unitQty = $product->getData('unit_qty');
        $unitDisplay = $product->getData('case_display');
        $pieceQty = $this->caseDisplay->getQtyPieceCaseForSaving($unitDisplay, $unitQty, $productQty);
        $product->setQty($pieceQty);

        $backUrl = $this->_redirect->getRefererUrl();

        $this->registry->register('profile', $profile);
        $this->registry->register('product_add', $product);
        $this->registry->register('new_spot_product_qty_assigned', $pieceQty);
        $this->registry->register('back_url', $backUrl);
        $this->registry->register('subscription_profile_obj', $profile);

        /**
         * set registry to apply catalogrule subscription
         */
        $this->registry->register(
            \Riki\Subscription\Model\Constant::RIKI_COURSE_ID,
            $profile->getCourseId()
        );
        $this->registry->register(
            \Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID,
            $profile->getSubProfileFrequencyID()
        );
    }

    /**
     * @param $profile
     * @param $product
     * @param $productQty
     * @return $this|bool
     */
    protected function checkAddSpotStockPoint($profile, $product, $productQty)
    {
        if (!$productQty) {
            $productQty = 1;
        }
        $unitQty = $product->getData('unit_qty');
        $unitDisplay = $product->getData('case_display');
        $qty = $this->caseDisplay->getQtyPieceCaseForSaving($unitDisplay, $unitQty, $productQty);
        $productId = $product->getId();
        $arrProduct = [
            $productId=>[
                'product'=>$product,
                'qty'=> $qty
            ]
        ];

        $isAllow = $this->validateStockPointProduct->checkProductAllowStockPoint($profile, $product, $arrProduct);
        if (!$isAllow) {
            $mess = __('The selected product is not allowed to buy with Stock Point.');
            $this->messageManager->addErrorMessage($mess);
            return $this->resultRedirectFactory->create()->setUrl(
                $this->_redirect->getRefererUrl()
            );
        }
        return false;
    }

    /**
     * @param $product
     * @param $profileId
     * @return $this|bool
     */
    protected function checkDeliveryType($product, $profileId)
    {
        $profileDeliveryType = $this->addSpotHelper->getDeliveryTypeOfProfile($profileId);
        if ($profileDeliveryType) {
            $productDeliveryType = $product->getData('delivery_type');
            if (!in_array($productDeliveryType, $profileDeliveryType)) {
                $this->messageManager->addErrorMessage(
                    __('The subscription just allow to add products in the same delivery type')
                );
                return $this->resultRedirectFactory->create()
                    ->setUrl($this->_redirect->getRefererUrl());
            }
        }
        return false;
    }

    /**
     * @param $profileId
     * @param $productId
     * @return $this|bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function checkNewSpotProductExistInProfile($profileId, $productId)
    {
        $validateAddSpot = $this->isNewSpotProductExistInProfile($profileId, $productId);
        if ($validateAddSpot == Constant::ADD_SPOT_PRODUCT_ERROR_SPOT_PRODUCT_IS_EXIST_LIKE_MAIN_PRODUCT) {
            $productRepository = $this->productRepository->getById($productId);
            $msg = $productRepository->getName()." ".__('already exists in profile please go to edit profile to edit');
            $this->messageManager->addErrorMessage($msg);
            return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRefererUrl());
        }
        return false;
    }

    /**
     * @param $profileId
     * @param $product
     * @param $qtyRequest
     *
     * @return $this|bool
     */
    protected function checkMaximumQtyRestriction($profileId, $product, $qtyRequest)
    {
        $pieceQty = $this->caseDisplay->getQtyPieceCaseForSaving(
            $product->getData('case_display'),
            $product->getData('unit_qty'),
            $qtyRequest
        );
        $caseDisplay = $this->caseDisplay->getCaseDisplayKey($product->getData('case_display'));
        $unitQty = $this->caseDisplay->validateQtyPieceCase(
            $product->getData('case_display'),
            $product->getData('unit_qty')
        );

        $param = [
            'product_id' => $product->getId(),
            'product_type' => $product->getTypeId(),
            'qty' => $pieceQty,
            'product_options' => '',
            'unit_case' => $caseDisplay,
            'unit_qty' => $unitQty,
            'gw_id' => '',
            'gift_message_id' => ''
        ];
        $objectDataSimulate = $this->profileData->makeObjectDataForSimulate($profileId, $param);
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
            return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRefererUrl());
        }

        return false;
    }

    /**
     * Validate order amount
     *
     * @param $profile
     * @param $product
     * @return null
     */
    protected function validateOrderAmount($profile, $product)
    {
        $message = null;
        if ($profile) {
            $courseModel = $course = $this->subscriptionOrderHelper->loadCourse($profile->getCourseId());
            $simulatedOrder = $this->simulator($profile, $courseModel, $product);
            if ($simulatedOrder) {
                $resultValidate = $this->subscriptionOrderHelper->validateAmountRestriction(
                    $simulatedOrder,
                    $courseModel,
                    $profile
                );
                if ($resultValidate['status']) {
                    //register data order simulated
                    $this->registry->register('simulate_order_after_add_spot_product', $simulatedOrder);
                } else {
                    $this->registry->unregister('product_add');
                    $message = $resultValidate['message'];
                }
            }
        }
        return $message;
    }

    /**
     * Simulate data
     *
     * @param $profile
     * @param $courseModel
     * @param $product
     * @return bool|object
     */
    protected function simulator($profile, $courseModel, $product)
    {
        $caseDisplay = $this->caseDisplay->getCaseDisplayKey($product->getData('case_display'));
        $newProduct = [
            'product_id' => $product->getId(),
            'qty' => $product->getQty(),
            'product_type' => $product->getTypeId(),
            'product_options' => '',
            'unit_case' => $caseDisplay,
            'unit_qty' => $product->getUnitQty(),
            'gw_id' => '',
            'gift_message_id' => ''
        ];
        $productCartData = $this->profileData->makeProductCartData($profile->getProfileId(), $newProduct);
        $objectData = new DataObject();
        $objectData->setData($profile->getData());
        $objectData->setData('course_data', $courseModel);
        $objectData->setData("product_cart", $productCartData);

        $simulatedOrder = $this->simulator->createSimulatorOrderHasData($objectData);
        if ($simulatedOrder) {
            return $simulatedOrder;
        }
        return false;
    }
}
