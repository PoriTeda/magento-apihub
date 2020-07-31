<?php

namespace Riki\Subscription\Helper\Profile\Controller;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Riki\Subscription\Model\Constant;
use Symfony\Component\Config\Definition\Exception\Exception;
use Riki\Subscription\Model\Profile\ProfileFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Filesystem\Driver\File;
use Riki\Subscription\Model\Profile\ProfileRepository;
use Riki\DeliveryType\Model\Delitype as DelitypeModel;
use Magento\Customer\Api\CustomerRepositoryInterface;

class AddMultipleProduct
{
    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $_dlHelper;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_profileData;

    /**
     * @var File
     */
    protected $_file;

    /**
     * @var ProfileRepository
     */
    protected $_profileRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJson;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;
    /**
     * @var array
     */
    protected $errorStockPoint = [];

    /**
     * @var array
     */
    protected $products = [];

    /**
     * @var bool
     */
    protected $isCleanDataStockPoint = false;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subscriptionOrderHelper;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * AddMultipleProduct constructor.
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface
     * @param ProfileRepository $profileRepository
     * @param File $file
     * @param \Riki\Subscription\Helper\Profile\Data $subProfileHelper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param ProfileFactory $profileFactory
     * @param ProductFactory $productFactory
     * @param \Riki\DeliveryType\Helper\Data $dlHelper
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     * @param \Riki\Subscription\Helper\Order $subscriptionOrderHelper
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Riki\Subscription\Helper\Profile\Data $subProfileHelper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        ProfileFactory $profileFactory,
        ProductFactory $productFactory,
        \Riki\DeliveryType\Helper\Data $dlHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Riki\Subscription\Helper\Order $subscriptionOrderHelper,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
    ) {
        $this->_customerRepository = $customerRepositoryInterface;
        $this->_resultJson = $jsonFactory;
        $this->_productRepository = $productRepositoryInterface;
        $this->_profileRepository = $profileRepository;
        $this->_file = $file;
        $this->_profileData = $subProfileHelper;
        $this->om = $objectManager;
        $this->_profileF = $profileFactory;
        $this->_productF = $productFactory;
        $this->_dlHelper = $dlHelper;
        $this->validateStockPointProduct =$validateStockPointProduct;
        $this->subscriptionOrderHelper = $subscriptionOrderHelper;
        $this->subscriptionValidator = $subscriptionValidator;
    }

    /**
     * @param $action
     * @return mixed
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Zend_Json_Exception
     */
    public function execute($action)
    {
        /** 1 - Params */
        $actionSession = $action->getProfileCache();
        $messageManager = $action->getMessageManager();

        $request = $this->_file->fileGetContents("php://input");
        $params = \Zend_Json::decode($request, true);

        $errorMessages = [];
        $isSuccess = true;

        $objProfileSession = $actionSession;
        // 1 - Validate
        if (empty($objProfileSession) || empty($objProfileSession->getData("profile_id"))) {
            $isSuccess = false;
            $errorMessages[] = __('Something went wrong, please reload page.');
            $result = $this->_resultJson->create();
            return $result->setData([
                'success' => $isSuccess,
                'error_messages' => $errorMessages
            ]);
        } else {
            $productInCart = $arrProductCartSession = $objProfileSession['product_cart'];
            $stockPointDiscountRate = $this->getStockPointDiscountRateFromCartProducts($arrProductCartSession);
            $profileId = $params['profile_id'];
            $versionProfileId = $this->_profileRepository->getProfileIdVersion($profileId);
            $profileModel = $this->_profileF->create()->load($profileId);
            $productWillAdd = isset($params['products']) ? $params['products'] : [];
            $productWillAdd = $this->processDataBeforeAddCart($objProfileSession, $productWillAdd);
            foreach ($productWillAdd as $product) {
                $productId = $product['product_additional_id'];
                $productQty = $product['product_additional_qty'];
                if (!$productQty) {
                    continue;
                }
                $unitQty = $product['unit_qty'];
                $unitCase = isset($product['unit_case']) ? strtoupper($product['unit_case']) : 'EA';
                $isAddition = 0;
                if (isset($product['is_addition']) && $product['is_addition']) {
                    $isAddition = 1;
                }
                if (!array_key_exists('product_address', $product)) {
                    $productAddress = $this->getDefaultShippingAddressId($actionSession);
                } else {
                    $productAddress = $product['product_address'];
                }

                $returnProfileLink = $profileId;
                if ($this->_profileData->getTmpProfile($profileId) !== false) {
                    $profileId = $this->_profileData->getTmpProfile($profileId)->getData('linked_profile_id');
                }

                if ($this->_profileData->getTmpProfile($profileId) !== false) {
                    $versionProfileId = $this->_profileData->getTmpProfile($profileId)->getData('linked_profile_id');
                }

                $isProfileSP = isset($product['is_profile_stock_point']) ? $product['is_profile_stock_point'] : false ;
                try {
                    /** 2 Params for 3 */
                    $data = [];
                    $data['profile_id'] = $versionProfileId;
                    $data['product_id'] = $productId;
                    $data['qty'] = $productQty;
                    $data['unit_case'] = strtoupper($unitCase);
                    $data['unit_qty'] = $unitQty;
                    $data['is_addition'] = $isAddition;
                    if ($productId && isset($this->products[$productId])) {
                        $productModel = $this->products[$productId];
                        $deliveryType = $productModel->getDeliveryType();
                        if ($this->validateProductBelongCurrentDeliveryType($actionSession, $deliveryType) == false) {
                            $isSuccess = false;
                            $mes =  __('The subscription just allow to add products in the same delivery type');
                            $errorMessages[] = $productModel->getName() . ': ' .$mes;
                            continue;
                        }

                        /**
                         * Validate product stock point
                         */
                        $isAllow = isset($product['is_allow_stock_point']) ? $product['is_allow_stock_point'] : false;
                        if ($isProfileSP && !$isAllow) {
                            continue;
                        }

                        // 3.1 - Group
                        foreach ($arrProductCartSession as $key => $item) {
                            if ($item->getData('profile_id') == $versionProfileId
                                && $item->getData('product_id') == $productId
                                && $item->getData('shipping_address_id') == $productAddress
                            ) {
                                /*
                                * check product allow spot order.
                                * if allow_spot_order set no.this product will appear as out
                                * of stock and can't be added to subscription profile.
                                */
                                if ($productModel->getId()) {
                                    if (!$productModel->getIsSalable()) {
                                        $isSuccess = false;
                                        $errorMessages[] = __('Cannot add product to this profile.');
                                    }
                                }

                                $item->setData('qty', $item['qty'] + $productQty);
                                if ($isAddition) {
                                    $item->setData('is_addition', 1);
                                }
                                $arrProductCartSession[$key] = $item;
                                $objProfileSession['product_cart'] = $arrProductCartSession;
                                $actionSession->setData(Constant::SESSION_PROFILE_EDIT, $objProfileSession);
                                continue 2;
                            }
                        }
                        /** 3.2 - Collect **/
                        $productCart = new DataObject();

                        if ($profileModel->getId()) {
                            try {
                                $customer = $this->_customerRepository->getById($profileModel->getData('customer_id'));
                                $defaultBillingAddress = $this->getBillingAddressId($actionSession, $returnProfileLink);
                                if ($defaultBillingAddress != '') {
                                    $data['billing_address_id'] = $defaultBillingAddress;
                                } else {
                                    /** Case not get billing address from old product data */
                                    $defaultBillingAddressId = $customer->getDefaultBilling();
                                    $data['billing_address_id'] = $defaultBillingAddressId;
                                }

                                if ($productAddress != null) {
                                    $data['shipping_address_id'] = $productAddress;
                                } else {
                                    $defaultShippingAddressId = $customer->getDefaultShipping();
                                    $data['shipping_address_id'] = $defaultShippingAddressId;
                                }
                            } catch (\Exception $e) {
                                $isSuccess = false;
                                $errorMessages[] = __('Customer not exist');
                            }
                        }
                        if ($productModel->getId()) {
                            $data['product_type'] = $productModel->getTypeId();
                            if ($productModel->getParentItemId() != '') {
                                $data['parent_item_id'] = $productModel->getParentItemId();
                            } else {
                                $data['parent_item_id'] = '';
                            }
                        }

                        /** 3.3 - Save **/
                        try {
                            $cartId = 'new_' . $data['product_id'] . '_' . $data['profile_id'] . '_' . $productAddress;
                            $data['cart_id'] = $cartId;
                            $data['stock_point_discount_rate'] = $stockPointDiscountRate;
                            $productCart->setData($data);
                            $arrProductCartSession[$data['cart_id']] = $productCart;
                            $objProfileSession['product_cart'] = $arrProductCartSession;
                            $objProfileSession[Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED] = true;
                        } catch (Exception $e) {
                            $isSuccess = false;
                            $errorMessages[] = __('Cannot add product to this profile.');
                        }
                    } else {
                        $isSuccess = false;
                        $errorMessages[] = __('Cannot add product to this profile.');
                    }
                } catch (\Exception $e) {
                    $isSuccess = false;
                    $errorMessages[] = __($e->getMessage());
                }
            }
        }

        /**
         * Show message for stock point
         */
        if (!empty($this->errorStockPoint)) {
            $isSuccess = false;
            $errorMessages[] = __('The selected product is not allowed to buy with Stock Point.');
        } else {
            if ($this->isCleanDataStockPoint) {
                $this->validateStockPointProduct->cleanDataStockPointSubCarrier($objProfileSession);
            }
        }
        
        if(!$isSuccess) { // if some products are failed add to cart, rollback the cart 
            //reset data before add product
            $objProfileSession['product_cart'] = $productInCart;
            $objProfileSession[Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED] = true;
            $actionSession->setData(Constant::SESSION_PROFILE_EDIT, $objProfileSession);
        } 
        
        /** save cache */
        $action->saveToCache($objProfileSession);

        /**
         * Validate order amount restriction
         */
        if (empty($errorMessages)) {
            $course = $this->subscriptionOrderHelper->loadCourse($objProfileSession->getCourseId());
            $amountValidationResult = $this->subscriptionOrderHelper->validateSimulateOrderAmountRestriction(
                $course,
                $objProfileSession
            );
            if (!$amountValidationResult['status']) {
                $isSuccess = false;
                $errorMessages[] = $amountValidationResult['message'];
                //reset data before add product
                $objProfileSession['product_cart'] = $productInCart;
                $objProfileSession[Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED] = true;
                $actionSession->setData(Constant::SESSION_PROFILE_EDIT, $objProfileSession);
            }
        }

        /* Validate maximum qty restriction */
        $productCarts = $objProfileSession->getProductCart();
        if (is_array($productCarts) && !empty($productCarts)) {
            $maximumQtyValidationResult = $this->subscriptionValidator->setProfileId($profileId)
                ->setProductCarts($productCarts)
                ->validateMaximumQtyRestriction();
            if ($maximumQtyValidationResult['error'] && !empty($maximumQtyValidationResult['product_errors'])) {
                $isSuccess = false;
                $message = $this->subscriptionValidator->getMessageMaximumError(
                    $maximumQtyValidationResult['product_errors'],
                    $maximumQtyValidationResult['maxQty']
                );
                $errorMessages[] = $message;
                //reset data before add product
                $objProfileSession['product_cart'] = $productInCart;
                $objProfileSession[Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED] = true;
                $actionSession->setData(Constant::SESSION_PROFILE_EDIT, $objProfileSession);
            }
        }

        $result = $this->_resultJson->create();
        if (!empty($errorMessages)) {
            $messageManager->addError(implode("<br>", $errorMessages));
        }
        return $result->setData([
            'success'   =>  $isSuccess,
            'error_messages'    =>  $errorMessages
        ]);
    }

    /**
     * Get default shipping address
     *
     * @param $actionSession
     * @return string
     */
    public function getDefaultShippingAddressId($actionSession)
    {
        $arrProductCartSession = $actionSession['product_cart'];
        foreach ($arrProductCartSession as $key => $item) {
            return $item->getData('shipping_address_id');
        }
        return '';
    }

    /**
     * Get billing address id
     *
     * @param $actionSession
     * @param $profileId
     * @return string
     */
    public function getBillingAddressId($actionSession, $profileId)
    {
        $arrProductCartSession = $actionSession['product_cart'];
        if (!empty($arrProductCartSession)) {
            foreach ($arrProductCartSession as $key => $item) {
                if ($item->getData('billing_address_id')) {
                    return $item->getData('billing_address_id');
                }
            }
        } else {
            if ($this->_profileData->getTmpProfile($profileId) !== false) {
                $profileId = $this->_profileData->getTmpProfile($profileId)->getData('linked_profile_id');
            }
            $productCartData = $this->_profileData->getArrProductCart($profileId);
            foreach ($productCartData as $key => $arrProductInfo) {
                if ($arrProductInfo['profile']->getData('billing_address_id') != '') {
                    return $arrProductInfo['profile']->getData('billing_address_id');
                }
            }
        }
        return '';
    }

    /**
     * Validate delivery type
     *
     * @param $actionSession
     * @param $newDeliveryType
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function validateProductBelongCurrentDeliveryType($actionSession, $newDeliveryType)
    {
        $arrGroupCoolNormalDm
            = [DelitypeModel::COOL, DelitypeModel::NORMAl, DelitypeModel::DM, DelitypeModel::COOL_NORMAL_DM];
        $arrProductCartSession = $actionSession['product_cart'];
        $currentDeliveryType = null;
        foreach ($arrProductCartSession as $item) {
            $currentDeliveryType = $this->_productRepository->getById($item->getData('product_id'))->getDeliveryType();
            if ($currentDeliveryType != null) {
                break;
            }
        }

        // Case not
        if ($currentDeliveryType == null) {
            return true;
        }

        if (in_array($currentDeliveryType, $arrGroupCoolNormalDm)) {
            if (in_array($newDeliveryType, $arrGroupCoolNormalDm)) {
                return true;
            } else {
                return false;
            }
        } else {
            if ($currentDeliveryType != $newDeliveryType) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check add product stock point
     *
     * @param $profileModel
     * @param $productId
     * @param $productQty
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function checkAddSpotStockPoint($profileModel, $productId, $productQty)
    {
        if (isset($this->products[$productId])) {
            $product = $this->products[$productId];
        } else {
            $product = $this->_productRepository->getById($productId);
        }

        $arrProduct = [
            $productId=>[
                'product'=>$product,
                'qty'=> $productQty
            ]
        ];

        $isAllow = $this->validateStockPointProduct->checkProductAllowStockPoint(
            $profileModel,
            $product,
            $arrProduct
        );
        if (!$isAllow) {
            $this->errorStockPoint[$product->getId()] = $product->getName();
        }
        return $isAllow;
    }

    /**
     * get current stock point discount of profile datama
     *
     * @param array $cartProducts
     * @return int
     */
    private function getStockPointDiscountRateFromCartProducts(array $cartProducts)
    {
        foreach ($cartProducts as $cartProduct) {
            if ($discountRate = $cartProduct->getData('stock_point_discount_rate')) {
                return $discountRate;
            }
        }

        return 0;
    }

    /**
     * @param $profileModel
     * @param $productWillAdd
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processDataBeforeAddCart($profileModel, $productWillAdd)
    {
        $allowSp = [];
        $notAllowSp = [];
        $isProfileSP = $this->validateStockPointProduct->checkProfileExistStockPoint($profileModel);
        foreach ($productWillAdd as $key => $product) {
            $productId = $product['product_additional_id'];
            $productQty = $product['product_additional_qty'];
            if ($productId && $productQty) {
                $productModel = $this->_productF->create()->load($productId);
                if ($productModel) {
                    $this->products[$productId] = $productModel;
                    $isAllow = $this->checkAddSpotStockPoint($profileModel, $productId, $productQty);
                    if ($this->validateStockPointProduct->canCleanDataSpCarrier()) {
                        $this->isCleanDataStockPoint = true;
                    }
                    $product['is_profile_stock_point'] = $isProfileSP;
                    if ($isAllow) {
                        $product['is_allow_stock_point'] = true;
                        $allowSp[] = $product;
                    } else {
                        $product['is_allow_stock_point'] = false;
                        $notAllowSp[] = $product;
                    }
                }
            }
        }

        /**
         * Clean error for profile stock point carrier
         */
        if ($this->isCleanDataStockPoint) {
            $this->errorStockPoint = [];
        }

        $productWillAdd = array_merge($allowSp, $notAllowSp);
        return $productWillAdd;
    }
}
