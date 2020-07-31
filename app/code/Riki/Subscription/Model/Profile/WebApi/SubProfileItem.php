<?php

namespace Riki\Subscription\Model\Profile\WebApi;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Riki\AdvancedInventory\Model\Stock as AdvancedInventoryStock;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay as CaseDisplay;
use Riki\DeliveryType\Model\Delitype as DelitypeModel;
use Riki\Subscription\Model\Constant;

class SubProfileItem implements \Riki\Subscription\Api\WebApi\SubProfileItemInterface
{
    const SYNC_FIELDS_PRODUCT_CART_STOCK_POINT
        = [
            'original_delivery_date'      => null,
            'original_delivery_time_slot' => null,
            'delivery_time_slot'          => null
        ];

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileData;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $profileRepository;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileF;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;

    /**
     * @var array
     */
    protected $itemExistOnProfileCart = [];

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * @var \Magento\Catalog\Model\Product\Option
     */
    protected $productOption;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productF;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Riki\Subscription\Helper\Data
     */
    protected $subscriptionHelperData;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subscriptionOrderHelper;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    protected $_productCart;

    /**
     * SubProfileItem constructor.
     *
     * @param \Riki\Subscription\Helper\Profile\Data             $profileData
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Riki\Subscription\Model\Profile\ProfileFactory    $profileFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface  $customerRepositoryInterface
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct  $validateStockPointProduct
     * @param \Riki\Subscription\Api\Data\ValidatorInterface     $subscriptionValidator
     * @param \Magento\Catalog\Model\Product\Option              $productOption
     * @param \Magento\Catalog\Api\ProductRepositoryInterface    $productRepositoryInterface
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Catalog\Model\ProductFactory              $productFactory
     * @param \Magento\Framework\UrlInterface                    $urlBuilder
     * @param \Riki\SubscriptionCourse\Model\CourseFactory       $courseFactory
     * @param \Riki\Subscription\Helper\Data                     $subscriptionHelperData
     * @param \Magento\Framework\Registry                        $registry
     * @param \Magento\Framework\Event\Manager                   $eventManager
     * @param \Riki\Subscription\Helper\Order                    $subscriptionOrderHelper
     * @param \Magento\Framework\DataObjectFactory               $dataObjectFactory
     */
    public function __construct(
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator,
        \Magento\Catalog\Model\Product\Option $productOption,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Helper\Data $subscriptionHelperData,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Event\Manager $eventManager,
        \Riki\Subscription\Helper\Order $subscriptionOrderHelper,
        \Magento\Framework\DataObjectFactory $dataObjectFactory
    ) {
        $this->profileData               = $profileData;
        $this->profileRepository         = $profileRepository;
        $this->profileF                  = $profileFactory;
        $this->customerRepository        = $customerRepositoryInterface;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->subscriptionValidator     = $subscriptionValidator;
        $this->productOption             = $productOption;
        $this->productRepository         = $productRepositoryInterface;
        $this->scopeConfig               = $scopeConfigInterface;
        $this->productF                  = $productFactory;
        $this->urlBuilder                = $urlBuilder;
        $this->courseFactory             = $courseFactory;
        $this->subscriptionHelperData    = $subscriptionHelperData;
        $this->registry                  = $registry;
        $this->eventManager              = $eventManager;
        $this->subscriptionOrderHelper   = $subscriptionOrderHelper;
        $this->dataObjectFactory         = $dataObjectFactory;
    }

    /**
     * generate profile data object
     *
     * @param $profileData
     * @param [] $additionalData
     * @return \Magento\Framework\DataObject
     */
    protected function generateProfileDataObject($profileData, $additionalData)
    {
        /*generate profile object by profile data*/
        $profileObject = $this->dataObjectFactory->create();

        $profileObject->setData($profileData);

        if (!empty($additionalData)) {
            /*set additional data for profile object*/
            foreach ($additionalData as $key => $data) {
                $profileObject->setData($key, $data);
            }
        }

        return $profileObject;
    }

    /**
     * redirect url
     *
     * @param $profileId
     *
     * @return string
     */
    protected function getStrRedirectWhenFailPath($profileId)
    {
        return $this->urlBuilder->getUrl('subscriptions/profile/edit', ['id' => $profileId]);
    }

    /**
     * @param $objectData
     *
     * @return array
     */
    protected function convertProfileObjectToArray($objectData){
        $productCart = [];
        foreach ($objectData['product_cart'] as $key => $item){
            $productCart['product_cart'][$key] = $item->getData();
        }

        return $productCart;
    }

    /**
     * Response API format
     *
     * @param             $message
     * @param string|null $redirect
     * @param string|null $content
     *
     * @return mixed
     */
    protected function responseData($message, $redirect = null, $content = null)
    {
        $response['response'] = [
            "code"     => self::RESPONSE_CODE,
            "message"  => $message,
            "redirect" => $redirect,
            "content"  => $content
        ];
        return $response;
    }

    /**
     * @param $product
     * @param $profileId
     *
     * @return array
     */
    protected function getProductDataFromPayLoad($product, $profileId){
        return [
            'product_id'                => $product->getProductId(),
            'product_type'              => $product->getProductType(),
            'cart_id'                   => $product->getCartId(),
            'parent_item_id'            => $product->getParentItemId(),
            'qty'                       => $product->getQty(),
            'unit_case'                 => strtoupper($product->getUnitCase() ? $product->getUnitCase() : CaseDisplay::PROFILE_UNIT_PIECE),
            'unit_qty'                  => $product->getUnitQty(),
            'price'                     => $product->getPrice(),
            'gw_id'                     => $product->getGwId(),
            'gift_message_id'           => $product->getGiftMessageId(),
            'billing_address_id'        => $product->getBillingAddressId(),
            'shipping_address_id'       => $product->getShippingAddressId(),
            'product_address'           => $product->getProductAddress() ? $product->getProductAddress() : $this->getDefaultShippingAddressId($profileId),
            'delivery_date'             => $product->getDeliveryDate(),
            'is_skip_seasonal'          => $product->getIsSkipSeasonal(),
            'skip_from'                 => $product->getSkipFrom(),
            'skip_to'                   => $product->getSkipTo(),
            'is_spot'                   => $product->getIsSpot(),
            'is_addition'               => $product->getIsAddition() ? $product->getIsAddition() : 0,
            'stock_point_discount_rate' => $product->getStockPointDiscountRate()
        ];
    }

    /**
     * Api add item to profile editing
     *
     * @param \Riki\Subscription\Api\Data\ProductCartInterface $productCart
     *
     * @return mixed|string
     * @throws LocalizedException
     */
    public function add(\Riki\Subscription\Api\Data\ProductCartInterface $productCart)
    {
        $profileId = $productCart->getProfileId();
        if ($this->profileData->getTmpProfile($profileId) !== false) {
            $profileId = $this->profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }

        //current product in cart
        $currentProductCart = [];
        foreach ($productCart->getCurrentItems() as $product){
            $currentProductCart[$product->getCartId()] = $this->generateProfileDataObject(
                $this->getProductDataFromPayLoad($product, $profileId),
                null
            );
        }

        //new item adding
        $newProductData = [];
        foreach ($productCart->getNewItems() as $product){
            $newProductData[] = $this->getProductDataFromPayLoad($product, $profileId);
        }

        $profileModel = $this->profileData->load($profileId);

        $profileObject = $this->generateProfileDataObject(
            $profileModel->getData(),
            [
                'course_data' => $profileModel->getCourseData(),
                'product_cart' => $currentProductCart
            ]
        );

        if ($profileObject == false) {
            $message['type'] = self::MESSAGE_TYPE_WARNING;
            $message['text'] = __('Something went wrong, please reload page.');
            return $this->responseData([$message], null, $this->convertProfileObjectToArray($profileObject));
        }

        $messages = $this->addProductsToProfile(
            $profileId,
            $newProductData,
            $profileObject
        );

        $strRedirectWhenFail = null;
        foreach ($messages as $message) {
            if ($message['type'] == self::MESSAGE_TYPE_EXCEPTION) {
                $strRedirectWhenFail = $this->getStrRedirectWhenFailPath($profileId);
            }
        }
        return $this->responseData($messages, $strRedirectWhenFail, $this->convertProfileObjectToArray($profileObject));
    }

    /**
     * @param       $profileId
     * @param array $productsData
     * @param       $profileObject
     *
     * @return array
     */
    protected function addProductsToProfile($profileId, array $productsData, $profileObject)
    {
        $isSuccess         = true;
        $errorType         = 0;
        $errorMessages     = [];
        $name              = '';
        $returnProfileLink = $profileId;

        if ($this->profileData->getTmpProfile($profileId) !== false) {
            $profileId = $this->profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }

        $objProfileSession = $profileObject;

        try {
            $profileModel = $this->profileF->create()->load($profileId);

            if ($profileModel->getId()) {
                $customer = $this->customerRepository->getById($profileModel->getData('customer_id'));
            } else {
                $message['type'] = self::MESSAGE_TYPE_EXCEPTION;
                $message['text'] = __('Profile does not exist.');
                return [$message];
            }
            $defaultBillingAddressId = $this->getBillingAddressId($profileObject, $returnProfileLink);
            if ($defaultBillingAddressId == '') {
                // Case not get billing address from old product data
                $defaultBillingAddressId = $customer->getDefaultBilling();
            }
            $arrProductCartSession = $objProfileSession['product_cart'];

            if (empty($objProfileSession) || empty($objProfileSession->getData("profile_id"))) {
                $message['type'] = self::MESSAGE_TYPE_EXCEPTION;
                $message['text'] = __('Something went wrong, please reload page.');
                return [$message];
            }

            $stockPointDiscountRate = $this->getStockPointDiscountRateFromCartProducts($arrProductCartSession);
            $isProfileStockPoint    = $this->validateStockPointProduct->checkProfileExistStockPoint($objProfileSession);
            if($isProfileStockPoint){
                $dataForStockPoint = $this->getDataForProductOfStockPoint($arrProductCartSession);
            }

            $productError       = [];
            $productErrorMaxQty = [];
            foreach ($productsData as $productData) {
                $productId  = $productData['product_id'];
                $productQty = $productData['qty'];
                $unitCase   = $productData['unit_case'];
                $unitQty    = $productData['unit_qty'];
                $isAddition = $productData['is_addition'];

                if (isset($productData['product_address'])) {
                    $productAddress = $productData['product_address'];
                } else {
                    $productAddress = $this->getCurrentProfileShippingAddressId(
                        $arrProductCartSession,
                        $customer,
                        $profileId
                    );
                }

                /** @var \Magento\Catalog\Model\Product $productModel */
                $productModel = $this->loadProductModel($productId);
                $name = $productModel->getName();

                if ($this->validateTotalQtyProductInCart(
                    $profileObject,
                    [
                        'product_id' => $productId,
                        'qty'        => $productQty,
                        'unit_case'  => $unitCase,
                        'unit_qty'   => $unitQty
                    ]
                )) {
                    $isSuccess                  = false;
                    $errorCode                  = 3;
                    $productError[$errorCode][] = $productModel->getName();
                    continue;
                }
                if ($this->validateProductBelongCurrentDeliveryType($profileObject, $productId) == false) {
                    $isSuccess                  = false;
                    $errorCode                  = 1;
                    $productError[$errorCode][] = $productModel->getName();
                    continue;
                }

                /**
                 * only validate when profile is stock point
                 */
                if ($isProfileStockPoint) {
                    $arrProduct = [
                        $productId => [
                            'product' => $productModel,
                            'qty'     => $productQty
                        ]
                    ];

                    $isAllow = $this->validateStockPointProduct->checkProductAllowStockPoint(
                        $objProfileSession,
                        $productModel,
                        $arrProduct
                    );

                    if (!$isAllow) {
                        $isSuccess                  = false;
                        $errorCode                  = 2;
                        $productError[$errorCode][] = $productModel->getName();
                        continue;
                    }
                }

                /** 3 Params for 3 */
                $data                = $productData;
                $data['is_addition'] = $isAddition;

                // 3.1 - Group
                foreach ($arrProductCartSession as $key => $item) {
                    /**
                     * Only add product exist on profile product cart
                     */
                    if ((int)$key) {
                        $this->itemExistOnProfileCart[$item->getData('product_id')] = $item->getData('product_id');
                    }

                    if ($item->getData('product_id') == $productId
                        && $item->getData('shipping_address_id') == $productAddress
                        && empty($item->getData('parent_item_id'))
                    ) {
                        /**
                         * check product allow spot order.
                         * if allow_spot_order set no.this product will appear as out of
                         * stock and can't  be added to subscription profile.
                         */

                        if ($productModel->getId()) {
                            if (!$productModel->getIsSalable()) {
                                $isSuccess       = false;
                                $errorMessages[] = __('%1: ', $productModel->getName()) .
                                                   __('Cannot add product to this profile.');

                                continue 2;
                            }
                        }

                        $item->setData('qty', $item['qty'] + $productQty);
                        /** Validate maximum for every product*/
                        $validateMaximumQty = $this->subscriptionValidator->setProfileId($profileId)
                                                                          ->setProductCarts([$item])
                                                                          ->validateMaximumQtyRestriction();
                        if ($validateMaximumQty['error'] && !empty($validateMaximumQty['product_errors'])) {
                            $productErrorMaxQty           = array_merge($productErrorMaxQty, $validateMaximumQty['product_errors']);
                            $productErrorMaxQty['maxQty'] = $validateMaximumQty['maxQty'];
                            $item->setData('qty', $item['qty'] - $productQty);
                            continue 2;
                        }

                        $item->setData('is_addition', ($isAddition && $item->getData('is_addition')));
                        $arrProductCartSession[$key]       = $item;
                        $objProfileSession['product_cart'] = $arrProductCartSession;
                        //$profileObject->setData(Constant::SESSION_PROFILE_EDIT, $objProfileSession);

                        continue 2;
                    }
                }

                /***
                 * Validate newly added product oss for profile is not stock point
                 */
                if (!$isProfileStockPoint && !isset($this->itemExistOnProfileCart[$productId])) {
                    if (!$productModel->getIsSalable()) {
                        $isSuccess       = false;
                        $errorMessages[] = __('%1: ', $productModel->getName()) .
                                           __('Cannot add product to this profile.');
                    }
                }

                if (!$isSuccess) {
                    continue;
                }

                $data['billing_address_id']  = $defaultBillingAddressId;
                $data['shipping_address_id'] = $productAddress;

                $data['product_type']    = $productModel->getTypeId();
                $customOptions           = $this->productOption->getProductOptionCollection($productModel);
                $data['product_options'] = \Zend_Json_Encoder::encode($customOptions->getData());
                $data['parent_item_id']  = ($productModel->getParentItemId()) ? $productModel->getParentItemId() : '';

                $data['stock_point_discount_rate'] = $stockPointDiscountRate;
                if ($isProfileStockPoint) {
                    foreach ($dataForStockPoint as $field => $value) {
                        $data[$field] = $value;
                    }
                }

                $data['cart_id'] = 'new_' . $data['product_id'] . '_' . $profileId . '_' . $productAddress;
                $productCart     = new DataObject($data);

                /** Validate maximum for every product*/
                $validateMaximumQty = $this->subscriptionValidator->setProfileId($profileId)
                                                                  ->setProductCarts([$productCart])
                                                                  ->validateMaximumQtyRestriction();
                if ($validateMaximumQty['error'] && !empty($validateMaximumQty['product_errors'])) {
                    $productErrorMaxQty           = array_merge($productErrorMaxQty, $validateMaximumQty['product_errors']);
                    $productErrorMaxQty['maxQty'] = $validateMaximumQty['maxQty'];
                    continue;
                }

                $arrProductCartSession[$data['cart_id']] = $productCart;
                $objProfileSession->setData('product_cart', $arrProductCartSession);
                $objProfileSession->setData(Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED, true);
                //$profileObject->setData(Constant::SESSION_PROFILE_EDIT, $objProfileSession);
            }

            /**
             * Clean data profile stock point carrier
             */
            if ($isProfileStockPoint && $this->validateStockPointProduct->canCleanDataSpCarrier()) {
                $this->validateStockPointProduct->cleanDataStockPointSubCarrier($objProfileSession);
            }
            $maxQty = 0;
            if (!empty($productErrorMaxQty)) {
                $maxQty = $productErrorMaxQty['maxQty'];
                unset($productErrorMaxQty['maxQty']);
                $errorCode                = 4;
                $productError[$errorCode] = $productErrorMaxQty;
                $isSuccess                = false;
            }
            foreach ($productError as $type => $listName) {
                if (!empty($listName)) {
                    $name = implode(" , ", $listName);
                    switch ($type) {
                        case 1:
                            $errorMessages[$name]
                                = __('The subscription just allow to add products in the same delivery type');
                            break;
                        case 2:
                            $errorMessages[$name]
                                = __('The selected product is not allowed to buy with Stock Point.');
                            break;
                        case 3:
                            $errorMessages[$name] = $name . ": " .
                                                    __(AdvancedInventoryStock::MORE_THAN_TOTAL_NUMBER_ITEM_ERROR_MESSAGE);
                            break;
                        case 4:
                            $errorMessages[$name] = $this->subscriptionValidator->getMessageMaximumError($listName, $maxQty);
                            break;

                    }
                }
            }
        } catch (LocalizedException $e) {
            $isSuccess       = false;
            $errorType       = 1;
            $errorMessages[] = $e->getMessage();
        } catch (\Exception $e) {
            $isSuccess       = false;
            $errorType       = 1;
            $errorMessages[] = __('Add product to profile do not successfully, please try again.');
        }

        if (!$isSuccess) {
            $message['type'] = self::MESSAGE_TYPE_ERROR;
            if($errorType == 1){
                $message['type'] = self::MESSAGE_TYPE_EXCEPTION;
            }
            $message['text'] = implode("\n", $errorMessages);
            return [$message];
        }
        $message['type'] = self::MESSAGE_TYPE_SUCCESS;
        $message['text'] = __('Add product %1 to profile successfully', $name);
        return [$message];
    }

    /**
     * @param array $cartProducts
     *
     * @return int
     */
    protected function getStockPointDiscountRateFromCartProducts(array $cartProducts)
    {
        foreach ($cartProducts as $cartProduct) {
            if ($discountRate = $cartProduct->getData('stock_point_discount_rate')) {
                return $discountRate;
            }
        }

        return 0;
    }

    /**
     * @param array $cartProducts
     *
     * @return array
     */
    protected function getDataForProductOfStockPoint($cartProducts)
    {
        $result = self::SYNC_FIELDS_PRODUCT_CART_STOCK_POINT;
        foreach ($cartProducts as $cartProduct) {
            foreach (self::SYNC_FIELDS_PRODUCT_CART_STOCK_POINT as $field => $defaultValue) {
                if ($cartProduct->getData($field) && $result[$field] == $defaultValue) {
                    $result[$field] = $cartProduct->getData($field);
                    continue;
                }
            }
        }

        return $result;
    }

    /**
     * Get profile current shipping address
     *
     * @param $profileProductCart
     * @param $customer
     * @param $profileId
     *
     * @return int|string
     */
    protected function getCurrentProfileShippingAddressId($profileProductCart, $customer, $profileId)
    {
        /*get shipping address id of profile item*/
        if ($profileProductCart) {
            foreach ($profileProductCart as $item) {
                if (isset($item['shipping_address_id']) && !empty($item['shipping_address_id'])) {
                    return $item['shipping_address_id'];
                }
            }
        }

        /**
         * for case that can not get shipping address of profile item,
         *      example current profile do not have any item
         *      get default shipping address from db
         */
        $rs = $this->getDefaultShippingAddressId($profileId);

        /**
         * for case can not get shipping address from profile item and db
         *      get default shipping id of customer
         */
        if (!$rs) {
            $rs = $customer->getDefaultShipping();
        }

        return $rs;
    }

    /**
     * @param $profileId
     *
     * @return string
     */
    protected function getDefaultShippingAddressId($profileId)
    {

        $profileMode    = $this->profileF->create()->load($profileId);
        $arrProductCart = $profileMode->getProductCart();
        foreach ($arrProductCart as $key => $item) {
            return $item->getData('shipping_address_id');
        }
        return '';
    }

    /**
     * Get billing address id
     *
     * @param $profileObject
     * @param $profileId
     *
     * @return string
     */
    protected function getBillingAddressId($profileObject, $profileId)
    {
        $arrProductCartSession = $profileObject['product_cart'];
        if (!empty($arrProductCartSession)) {
            foreach ($arrProductCartSession as $key => $item) {
                if ($item->getData('billing_address_id')) {
                    return $item->getData('billing_address_id');
                }
            }
        } else {
            if ($this->profileData->getTmpProfile($profileId) !== false) {
                $profileId = $this->profileData->getTmpProfile($profileId)->getData('linked_profile_id');
            }
            $productCartData = $this->profileData->getArrProductCart($profileId);
            foreach ($productCartData as $key => $arrProductInfo) {
                if ($arrProductInfo['profile']->getData('billing_address_id') != '') {
                    return $arrProductInfo['profile']->getData('billing_address_id');
                }
            }
        }
        return '';
    }

    /**
     * Validate To
     *
     * @param $profileObject
     * @param $arrNewProduct
     *
     * @return bool
     */
    protected function validateTotalQtyProductInCart($profileObject, $arrNewProduct)
    {
        $result                = false;
        $totalQty              = 0;
        $arrProductCartSession = $profileObject['product_cart'];
        foreach ($arrProductCartSession as $item) {
            $dataItem = $item->getData();
            if (array_key_exists('unit_case', $dataItem) &&
                array_key_exists('qty', $dataItem) &&
                array_key_exists('unit_qty', $dataItem)
            ) {
                if (strtoupper($dataItem['unit_case']) == CaseDisplay::PROFILE_UNIT_CASE) {
                    $totalQty = $totalQty + $dataItem['qty'] / $dataItem['unit_qty'];
                } else {
                    $totalQty = $totalQty + $dataItem['qty'];
                }
            }
        }

        if (array_key_exists('unit_case', $arrNewProduct) &&
            array_key_exists('unit_qty', $arrNewProduct) &&
            array_key_exists('qty', $arrNewProduct)) {
            if (strtoupper($arrNewProduct['unit_case']) == CaseDisplay::PROFILE_UNIT_CASE) {
                $totalQty = $totalQty + $arrNewProduct['qty'] / $arrNewProduct['unit_qty'];
            } else {
                $totalQty = $totalQty + $arrNewProduct['qty'];
            }
        }
        $maximumOrderQtyConfig = (int)$this->getConfig(
            AdvancedInventoryStock::ADVANCED_INVENTORY_MAXIMUM_CART_STOCK
        );
        if ($maximumOrderQtyConfig > 0 && $totalQty > $maximumOrderQtyConfig) {
            return true;
        }
        return $result;
    }

    /**
     * Validate Product Belong Current Delivery Type
     *
     * @param $profileObject
     * @param $newProductId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function validateProductBelongCurrentDeliveryType($profileObject, $newProductId)
    {
        $arrGroupCoolNormalDm  = [DelitypeModel::COOL, DelitypeModel::NORMAl, DelitypeModel::DM, DelitypeModel::COOL_NORMAL_DM];
        $arrProductCartSession = $profileObject['product_cart'];
        $currentDeliveryType   = null;
        foreach ($arrProductCartSession as $item) {
            $currentDeliveryType = $this->productRepository->getById($item->getData('product_id'))->getDeliveryType();
            if ($currentDeliveryType != null) {
                break;
            }
        }

        // Case not
        if ($currentDeliveryType == null) {
            return true;
        }

        $newDeliveryType = $this->productRepository->getById($newProductId)->getDeliveryType();

        if ($profileObject->getData("stock_point_profile_bucket_id")) {
            if ($newDeliveryType == DelitypeModel::NORMAl || $newDeliveryType == DelitypeModel::DM) {
                return true;
            }
            return false;
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
     * Get Store Config
     *
     * @param $path
     *
     * @return mixed
     */
    protected function getConfig($path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param $productId
     *
     * @return mixed
     */
    protected function loadProductModel($productId)
    {
        return $this->productF->create()->load($productId);
    }

    /**
     * Update item in profile
     *
     * @param \Riki\Subscription\Api\Data\Profile\ProductInterface $product
     *
     * @return mixed|string
     */
    public function update(\Riki\Subscription\Api\Data\Profile\ProductInterface $product)
    {
        $courseId          = $product->getCourseId();
        $frequencyUnit     = $product->getFrequencyUnit();
        $frequencyInterval = $product->getFrequencyInterval();
        $productId         = $product->getProductId();
        $qtyChange         = $product->getQty();

        $courseFactory = $this->courseFactory->create();
        $frequencyId = $courseFactory->checkFrequencyEntitiesExitOnDb($frequencyUnit, $frequencyInterval);
        $this->registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);
        $this->registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);

        /** @var \Magento\Catalog\Model\Product $product */
        $productModel = $this->loadProductModel($productId);
        if ($name = $productModel->getName()) {
            $productModel->setQty($qtyChange);
            $content = $this->getFinalProductPrice($productModel);
            if($content){
                $message['type'] = self::MESSAGE_TYPE_SUCCESS;
                $message['text'] = __('Update product %1 success', $name);
                return $this->responseData([$message], null, $content);
            }
        }else{
            $message['type'] = self::MESSAGE_TYPE_ERROR;
            $message['text'] = __('The product do not exists. Please reload the page and try again.');
            return $this->responseData([$message]);
        }

        $message['type'] = self::MESSAGE_TYPE_WARNING;
        $message['text'] = __('Something went wrong, please reload page.');
        return $this->responseData([$message]);
    }

    /**
     * Return new product price data
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    protected function getFinalProductPrice(\Magento\Catalog\Model\Product $product)
    {
        $price = 0;
        $total = 0;

        if ($qty = $product->getQty()) {
            $unitQty = 1;

            if ($product->getCaseDisplay() == CaseDisplay::CD_CASE_ONLY) {
                $unitQty = max((int)$product->getUnitQty(), 1);
            }

            $price = $this->subscriptionHelperData->getProductPriceInProfileEditPage($product, $qty);

            $total = $price * $qty;
            $price = $price * $unitQty;
        }

        return [
            "item_price_number"       => $price,
            "item_price"              => $price.'円',
            "total_item_price_number" => $total,
            "total_item_price"        => $total.'円'
        ];
    }


    /**
     * Api delete item in profile
     * -- Use current product cart data from request instead of data from database
     *
     * @param \Riki\Subscription\Api\Data\ProductCartInterface $productCart
     *
     * @return mixed|string
     * @throws LocalizedException
     */
    public function delete(\Riki\Subscription\Api\Data\ProductCartInterface $productCart)
    {
        $profileId     = $productCart->getProfileId();
        $productCartId = $productCart->getProductCartDeleteId();
        $allItem       = $productCart->getAllItemDelete();

        $currentProductCart = [];
        foreach ($productCart->getCurrentItems() as $product){
            $currentProductCart[$product->getCartId()] = $this->generateProfileDataObject(
                $this->getProductDataFromPayLoad($product, $profileId),
                null
            );
        }

        $profileModel = $this->profileData->load($profileId);
        if (!$profileModel) {
            $message['type'] = self::MESSAGE_TYPE_ERROR;
            $message['text'] = __("Some thing went wrong, please reload page.");
            return $this->responseData([$message]);
        }

        $profileObject = $this->generateProfileDataObject(
            $profileModel->getData(),
            [
                'course_data' => $profileModel->getCourseData(),
                'product_cart' => $currentProductCart
            ]
        );

        try {
            $validateResult = $this->validateDeleteProductWithProfileStatus($profileModel);
            if (is_array($validateResult)) {
                $message['type'] = self::MESSAGE_TYPE_ERROR;
                $message['text'] = __('Cannot delete this product');
                $response['message'][] = $message;
                $response['message'][] = $validateResult;
                return $this->responseData($response, null, $this->convertProfileObjectToArray($profileObject));
            }

            /**
             * Load product
             */
            $this->_productCart = $profileObject['product_cart'];

            $oldProductCart = $this->subscriptionOrderHelper->cloneProductCartData($this->_productCart);

            /**
             * Check product delete single or multiple
             */
            if (isset($allItem) && $allItem != null) {
                if ((!isset($productCartId) || $productCartId == null) && $allItem == -1) {
                    $deleteReturn = $this->deleteAllItemCheck(null, $profileObject);
                } else {
                    $deleteReturn = $this->deleteAllItemCheck($allItem, $profileObject);
                }
            } else {
                $deleteReturn = $this->deleteOneItem($productCartId, $profileObject);
            }
            if(is_array($deleteReturn)){
                return $deleteReturn;
            }
            $profileObject['product_cart'] = $this->_productCart;
            $subscriptionCourse = $this->subscriptionOrderHelper->loadCourse($profileModel->getData('course_id'));
            $validateResult = $this->subscriptionOrderHelper->validateSimulateOrderAmountRestriction($subscriptionCourse, $profileObject);
            if (!$validateResult['status']) {
                $profileObject['product_cart'] = $oldProductCart;
                $profileObject[Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED] = false;

                $message['type'] = self::MESSAGE_TYPE_EXCEPTION;
                $message['text'] = $validateResult['message'];
                return $this->responseData([$message], null, $this->convertProfileObjectToArray($profileObject));
            } else {
                $profileObject[Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED] = true;
                $message['type'] = self::MESSAGE_TYPE_SUCCESS;
                $message['text'] = __('Product temporarily deleted, please click "Confirm Changes" button to delete actually');
                return $this->responseData([$message], null, $this->convertProfileObjectToArray($profileObject));
            }
        } catch (\Exception $e) {
           $message['type'] = self::MESSAGE_TYPE_EXCEPTION;
           $message['text'] = $e->getMessage();
           return $this->responseData([$message], null, $this->convertProfileObjectToArray($profileObject));
        }
    }

    /**
     * @param $productCart
     * @param $deleteProductObject
     *
     * @return array
     */
    protected function checkAndDeleteChildProductOfBundle($productCart, $deleteProductObject)
    {
        $arrProductDelete = array();
        $productType = $deleteProductObject->getData('product_type');
        if ($productType == 'bundle') {
            $productId = $deleteProductObject->getData('product_id');
            foreach ($productCart as $item) {
                if ($item->getData('parent_item_id') == $productId) {
                    $arrProductDelete[] = $item->getData('cart_id');
                }
            }
        }
        return $arrProductDelete;
    }

    /**
     * Do not allow to delete product if the current profile is in stage
     *
     * @param $profile
     * @return bool|array
     */
    protected function validateDeleteProductWithProfileStatus(\Riki\Subscription\Model\Profile\Profile $profile)
    {
        if ($profile->isWaitingToDisengaged()) {
            return true;
        }

        if ($profile->isInStage()) {
            $message['type'] = self::MESSAGE_TYPE_NOTICE;
            $message['text'] = __('Profile is in stage');

            return $message;
        }

        return true;
    }

    /**
     * Delete one item
     *
     * @param $productCartId
     * @param $profileObject
     *
     * @return string
     */
    protected function deleteOneItem($productCartId, $profileObject)
    {
        if (!$productCartId || !array_key_exists($productCartId,$this->_productCart)) {
            $message['type'] = self::MESSAGE_TYPE_EXCEPTION;
            $message['text'] = __('The product do not exists. Please reload the page and try again.');
            return $this->responseData([$message], null, $this->convertProfileObjectToArray($profileObject));
        }

        $arrProductDelete = $this->checkAndDeleteChildProductOfBundle($this->_productCart, $this->_productCart[$productCartId]);
        unset($this->_productCart[$productCartId]);
        if (count($arrProductDelete) > 0) {
            foreach ($arrProductDelete as $productCartDeleteId) {
                unset($this->_productCart[$productCartDeleteId]);
            }
        }
        return true;
    }

    /**
     * @param $allItemDelete
     * @param $profileObject
     *
     * @return array
     */
    protected function deleteAllItemCheck($allItemDelete, $profileObject)
    {
        if ($allItemDelete != null) {
            if (count($allItemDelete) > 0) {
                foreach ($allItemDelete as $productCartId) {
                    $deleteReturn = $this->deleteOneItem($productCartId, $profileObject);
                    if(is_array($deleteReturn)){
                        return $deleteReturn;
                    }
                }
            } else {
                $message['type'] = self::MESSAGE_TYPE_EXCEPTION;
                $message['text'] = __('The product do not exists. Please reload the page and try again.');
                return $this->responseData([$message], null, $this->convertProfileObjectToArray($profileObject));
            }
        } else if (!empty($this->_productCart)) {
            foreach ($this->_productCart as $key => $value) {
                $deleteReturn = $this->deleteOneItem($key, $profileObject);
                if(is_array($deleteReturn)){
                    return $deleteReturn;
                }
            }
        }
    }
}
