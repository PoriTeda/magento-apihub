<?php

namespace Riki\Subscription\Controller\Adminhtml\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Riki\Subscription\Model\Constant;
use Riki\Subscription\Model\Profile\ProfileFactory;
use Magento\Catalog\Model\ProductRepository;

class ConfirmSpotProduct extends Action
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileData;

    /**
     * @var bool
     */
    protected $isStockPointProfile = false;

    /**
     * @var \Riki\PointOfSale\Model\DataMigration
     */
    protected $dataMigration;

    /**
     * @var \Riki\AdvancedInventory\Model\Assignation
     */
    protected $assignation;

    /**
     * @var \Riki\Subscription\Helper\StockPoint\Data
     */
    protected $stockPointHelper;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * ConfirmSpotProduct constructor.
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param ProductRepository $productRepository
     * @param ProfileFactory $profileFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\PointOfSale\Model\DataMigration $dataMigration
     * @param \Riki\AdvancedInventory\Model\Assignation $assignation
     * @param \Riki\Subscription\Helper\StockPoint\Data $stockPointHelper
     * @param Context $context
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        ProductRepository $productRepository,
        ProfileFactory $profileFactory,
        \Magento\Framework\Registry $registry,
        \Riki\PointOfSale\Model\DataMigration $dataMigration,
        \Riki\AdvancedInventory\Model\Assignation $assignation,
        \Riki\Subscription\Helper\StockPoint\Data $stockPointHelper,
        Context $context,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
    ) {
        $this->stockPointHelper = $stockPointHelper;
        $this->assignation = $assignation;
        $this->dataMigration = $dataMigration;
        $this->profileFactory = $profileFactory;
        $this->profileData = $helperProfile;
        $this->productRepository = $productRepository;
        $this->registry = $registry;
        $this->subscriptionValidator = $subscriptionValidator;
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $postData = $this->getRequest()->getParams();
        $profileId = isset($postData['id']) ? $postData['id'] : null;
        if (!$profileId || empty($postData)) {
            return $this->resultRedirectFactory->create()
                ->setUrl($this->_request->getServer('HTTP_REFERER'));
        }

        $profile = $this->profileFactory->create()->load($profileId);
        if (!$profile->getId()) {
            $this->messageManager->addError(__('The subscription profile no longer exists'));

            return $this->resultRedirectFactory->create()
                ->setUrl($this->_request->getServer('HTTP_REFERER'));
        }

        if ($this->profileData->isTmpProfileId($profileId, $profile)) {
            return $this->resultRedirectFactory->create()
                ->setUrl($this->_request->getServer('HTTP_REFERER'));
        }

        if ($profile->isStockPointProfile()) {
            $this->isStockPointProfile = true;
        }
        /** validate qty before confirm */
        $validateQty = $this->validateQty($profile, $postData);
        if (isset($validateQty['error']) && $validateQty['error']) {
            $this->messageManager->addError($validateQty['message']);

            return $this->resultRedirectFactory->create()
                ->setUrl($this->_request->getServer('HTTP_REFERER'));
        }
        $productInfo = [];
        if (isset($postData['checkSpotIds'])) {
            /**
             * Multiple spot product
             */
            $productInfo = $this->_processMultipleCheckSpot($profileId, $postData['checkSpotIds']);
            if (isset($productInfo['productNotExist'])) {
                $this->messageManager->addError($productInfo['message']);

                return $this->resultRedirectFactory->create()
                    ->setPath('/');
            } elseif (isset($productInfo['productExistOnSubscription'])) {
                $this->messageManager->addError($productInfo['message']);

                return $this->resultRedirectFactory->create()
                    ->setUrl($this->_request->getServer('HTTP_REFERER'));
            }
        } else {
            /**
             * Single spot product
             */
            $isValidateProduct = $this->_validateSpotProduct($profileId, $postData);
            if (isset($isValidateProduct['productNotExist'])) {
                $this->messageManager->addError($productInfo['message']);

                return $this->resultRedirectFactory->create()
                    ->setPath('/');
            } elseif (isset($isValidateProduct['productExistOnSubscription'])) {
                $message = null;
                if (isset($productInfo['productExistOnSubscription'])) {
                    $message = $productInfo['productExistOnSubscription'];
                } elseif (isset($isValidateProduct['message'])) {
                    $message = $isValidateProduct['message'];
                }

                $this->messageManager->addError($message);

                return $this->resultRedirectFactory->create()
                    ->setUrl($this->_request->getServer('HTTP_REFERER'));
            }

            $productInfo[] = $this->_getDataProductInfo($postData);
        }

        $this->registry->register('subscription-confirm-add-spot-profile-id', $profileId);
        $this->registry->register('subscription-confirm-add-spot-product-add-info', $productInfo);
        $this->registry->register('subscription_profile_obj', $profile);

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);

        $resultPage->getConfig()->getTitle()->prepend(__('Subscription Profile'));
        $resultPage->getConfig()->getTitle()->prepend(__('Add Spot Product'));

        return $resultPage;
    }

    /**
     * Validate spot product is exits in product cart
     *
     * @param $profileId
     * @param $productId
     *
     * @return int
     */
    protected function _isNewSpotProductExistInProfile($profileId, $productId)
    {
        $error = 0;

        if ($this->profileData->getTmpProfile($profileId) !== false) {
            $profileId = $this->profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }

        $arrProductInProfile = $this->profileData->getArrProductCart($profileId);
        $arrProductIdOfProductCart = array_keys($arrProductInProfile);

        if (in_array($productId, $arrProductIdOfProductCart)) {
            if ($arrProductInProfile[$productId]['profile']->getData('is_spot') == 1) {
                $error = Constant::ADD_SPOT_PRODUCT_ERROR_SPORT_PRODUCT_IS_EXIST_LIKE_SPOT;
            } else {
                $error = Constant::ADD_SPOT_PRODUCT_ERROR_SPOT_PRODUCT_IS_EXIST_LIKE_MAIN_PRODUCT;
            }
        }

        return $error;
    }

    /**
     * @param $profileId
     * @param $postData
     *
     * @return array
     */
    protected function _validateSpotProduct($profileId, $postData)
    {
        $productId = isset($postData['productId']) ? $postData['productId'] : null;
        $returnMessage = [];

        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            $product = null;
        }

        if (!$product) {
            $returnMessage['message'] = __('The selected product no longer exists');
            $returnMessage['productNotExist'] = true;

            return $returnMessage;
        }

        if ($this->isStockPointProfile) {
            $validate = $this->stockPointHelper->validateAddProductToStockPoint($product, $postData['qty']);
            if (!$validate) {
                $returnMessage['message'] =
                    __('The selected product is not allowed to buy with Stock Point.');
                $returnMessage['productExistOnSubscription'] = true;

                return $returnMessage;
            }
        }

        $validateAddSpot = $this->_isNewSpotProductExistInProfile($profileId, $productId);
        if ($validateAddSpot == Constant::ADD_SPOT_PRODUCT_ERROR_SPOT_PRODUCT_IS_EXIST_LIKE_MAIN_PRODUCT) {
            $returnMessage['message'] = __(
                '%1 already exists in the profile, please choose other product.',
                $product->getName()
            );
            $returnMessage['productExistOnSubscription'] = true;
            return $returnMessage;
        }

        return $returnMessage;
    }

    /**
     * @param $postData
     * @return array
     */
    protected function _getDataProductInfo($postData)
    {
        $productInfo = [];
        $productId = isset($postData['productId']) ? $postData['productId'] : null;
        $caseDisplay = isset($postData['case']) ? $postData['case'] : null;
        $unit = isset($postData['unit']) ? $postData['unit'] : null;
        $qty = isset($postData['qty']) ? $postData['qty'] : null;

        // product data add
        if (strtoupper($caseDisplay) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
            $qty = $qty * $unit;
        }

        $productInfo['product_id'] = $productId;
        $productInfo['qty'] = $qty;
        $productInfo['product_options'] = '';
        $productInfo['unit_case'] = strtoupper($caseDisplay);
        $productInfo['unit_qty'] = $unit;
        $productInfo['gw_id'] = '';
        $productInfo['gift_message_id'] = '';

        return $productInfo;
    }

    /**
     * @param $profileId
     * @param $spotProductIds
     *
     * @return array
     */
    protected function _processMultipleCheckSpot($profileId, $spotProductIds)
    {
        $productInfo = [];

        try {
            $spotProductIds = \Zend_Json::decode($spotProductIds);
        } catch (\Zend_Json_Exception $e) {
            $spotProductIds = null;
        }

        if ($spotProductIds && is_array($spotProductIds) && !empty($spotProductIds)) {
            foreach ($spotProductIds as $arrPost) {
                // Validate product
                $isValidateProduct = $this->_validateSpotProduct($profileId, $arrPost);
                if (!empty($isValidateProduct)) {
                    return $isValidateProduct;
                } else {
                    $productInfo[] = $this->_getDataProductInfo($arrPost);
                }
            }
        }

        return $productInfo;
    }

    /**
     * @param \Riki\Subscription\Model\Profile\Profile $profileModel
     * @param $postData
     * @return array
     */
    private function validateQty($profileModel, $postData)
    {
        $listProduct = [];
        if (isset($postData['checkSpotIds'])) {
            try {
                $spotProductIds = $postData['checkSpotIds'];
                $spotProductIds = \Zend_Json::decode($spotProductIds);
            } catch (\Zend_Json_Exception $e) {
                $spotProductIds = null;
            }

            if ($spotProductIds && is_array($spotProductIds) && !empty($spotProductIds)) {
                $data = $this->processDataBeforeValidate($spotProductIds, $profileModel);
                if ($data['error']) {
                    return [
                        'error' => true,
                        'message' => $data['message']
                    ];
                }
                $listProduct = $data['products'];
            }
        } else {
            $spotProductId[] = [
                'productId' => $postData['productId'],
                'qty' => $postData['qty'],
                'case' => $postData['case'],
                'unit' => $postData['unit']
            ];
            $data = $this->processDataBeforeValidate($spotProductId, $profileModel);
            if ($data['error']) {
                return [
                    'error' => true,
                    'message' => $data['message']
                ];
            }
            $listProduct = $data['products'];
        }

        $validateMaximumQty = $this->subscriptionValidator->setProfileId($profileModel->getProfileId())
            ->setProductCarts($listProduct)
            ->validateMaximumQtyRestriction();
        if ($validateMaximumQty['error']) {
            $productErrors = $validateMaximumQty['product_errors'];
            $maxQty = $validateMaximumQty['maxQty'];
            return [
                'error' => true,
                'message' => $this->subscriptionValidator->getMessageMaximumError($productErrors, $maxQty)
            ];
        }
    }

    /**
     * @param array $data
     * @param $profileModel
     * @return array
     */
    private function processDataBeforeValidate(array $data, $profileModel)
    {
        $productSelected = [];
        foreach ($data as $info) {
            try {
                $productId = $info['productId'];
                $qty = $info['qty'];
                $product = $this->productRepository->getById($productId);
            } catch (NoSuchEntityException $e) {
                $result = [
                    'message' =>__('The selected product no longer exists'),
                    'error' => true
                ];
                return $result;
            }

            $product->setQty($qty);
            $productSelected[$productId] = $product;
        }

        /* Merge product of profile with product add new */
        foreach ($profileModel->getProductCart() as $productCart) {
            $profileProductId = $productCart['product_id'];
            if (isset($productSelected[$profileProductId])) {
                $quantityAdd = (int)$productSelected[$profileProductId]->getQty();
                $quantityProductCart = (int)$productCart['qty'];
                $productSelected[$profileProductId]->setQty($quantityAdd + $quantityProductCart);
            } else {
                $quantityProductCart = (int)$productCart['qty'];
                try {
                    $productModel = $this->productRepository->getById($profileProductId);
                    $productModel->setQty($quantityProductCart);
                    $productSelected[$profileProductId] = $profileModel;
                } catch (NoSuchEntityException $e) {
                    $result = [
                        'message' =>__('The selected product no longer exists'),
                        'error' => true
                    ];
                    return $result;
                }
            }
        }

        $result = [
            'error' => false,
            'products' => $productSelected
        ];
        return $result;
    }
}
