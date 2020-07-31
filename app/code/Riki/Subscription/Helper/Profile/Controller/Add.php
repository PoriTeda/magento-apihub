<?php

namespace Riki\Subscription\Helper\Profile\Controller;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Riki\Subscription\Model\Constant;
use Riki\Subscription\Model\Profile\ProfileFactory;
use Magento\Catalog\Model\ProductFactory;
use Riki\DeliveryType\Model\Delitype as DelitypeModel;
use Riki\AdvancedInventory\Model\Stock as AdvancedInventoryStock;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay as CaseDisplay;

class Add
{
    const SYNC_FIELDS_PRODUCT_CART_STOCK_POINT = [
        'original_delivery_date' => null,
        'original_delivery_time_slot' => null,
        'delivery_time_slot' => null
    ];
    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $dlHelper;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileData;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJson;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Catalog\Model\Product\Option
     */
    protected $productOption;
    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;

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
     * @var array
     */
    protected $itemExistOnProfileCart = [];

    protected $profileCache;

    protected $subscriptionValidator;

    /**
     * Add constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Riki\Subscription\Helper\Profile\Data $subProfileHelper
     * @param ProfileFactory $profileFactory
     * @param ProductFactory $productFactory
     * @param \Riki\DeliveryType\Helper\Data $dlHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Magento\Catalog\Model\Product\Option $productOption
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     * @param \Riki\PointOfSale\Model\DataMigration $dataMigration
     * @param \Riki\AdvancedInventory\Model\Assignation $assignation
     * @param \Riki\Subscription\Helper\StockPoint\Data $stockPointHelper
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCache
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Riki\Subscription\Helper\Profile\Data $subProfileHelper,
        ProfileFactory $profileFactory,
        ProductFactory $productFactory,
        \Riki\DeliveryType\Helper\Data $dlHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Catalog\Model\Product\Option $productOption,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Riki\PointOfSale\Model\DataMigration $dataMigration,
        \Riki\AdvancedInventory\Model\Assignation $assignation,
        \Riki\Subscription\Helper\StockPoint\Data $stockPointHelper,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCache
    ) {
        $this->stockPointHelper = $stockPointHelper;
        $this->scopeConfig = $scopeConfigInterface;
        $this->customerRepository = $customerRepositoryInterface;
        $this->resultJson = $jsonFactory;
        $this->productRepository = $productRepositoryInterface;
        $this->profileRepository = $profileRepository;
        $this->file = $file;
        $this->profileData = $subProfileHelper;
        $this->dlHelper = $dlHelper;
        $this->productOption = $productOption;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->profileF = $profileFactory;
        $this->productF = $productFactory;
        $this->productOption = $productOption;
        $this->dataMigration = $dataMigration;
        $this->assignation = $assignation;
        $this->profileCache = $profileCache;
        $this->subscriptionValidator = $subscriptionValidator;
    }

    /**
     * @return \Magento\Framework\Controller\Result\JsonFactory
     */
    public function getResultJson()
    {
        return $this->resultJson;
    }

    /**
     * @param \Riki\Subscription\Controller\Profile\Add $action
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Zend_Json_Exception
     */
    public function execute($action)
    {

        // 1 - Params
        $actionCache = $action->getProfileCache();
        $strRedirectWhenFail = $action->getStrRedirectWhenFailPath();
        $messageManager = $action->getMessageManager();
        if ($actionCache == false) {
            if ($action->getRequest()->isXmlHttpRequest()) {
                $messageManager->addError(__('Something went wrong, please reload page.'));
            }
            $result = $this->resultJson->create();
            return $result->setData(['success' => false, 'message' => __('Something went wrong, please reload page.')]);
        }
        $isXmlHttpRequest = false;
        if ($action->getRequest()->isXmlHttpRequest() and
            !($action instanceof \Riki\Subscription\Controller\Adminhtml\Profile\Add)
        ) {
            $isXmlHttpRequest = true;
            $request = $this->file->fileGetContents("php://input");
            $params = \Zend_Json::decode($request, true);
            $aProductAdded = $params[0];
            $profileId = $aProductAdded['profile_id'];
            $productId = $aProductAdded['product_id'];
            $productQty = $aProductAdded['product_main_qty'];
            $unitQty = $aProductAdded['unit_qty'];

            if (isset($aProductAdded['unit_case'])) {
                $unitCase = $aProductAdded['unit_case'];
            } else {
                $unitCase = CaseDisplay::PROFILE_UNIT_PIECE;
            }

            $isAddition = 0;
            if (isset($aProductAdded['is_addition'])) {
                $isAddition = 1;
            }
            if (!array_key_exists('product_address', $aProductAdded)) {
                $productAddress = $this->getDefaultShippingAddressId($profileId);
            } else {
                $productAddress = $params[0]['product_address'];
            }
        } else {
            $post = $action->getRequest()->getParams();
            $profileId = $post['id'];
            $productId = $post['product_id'];
            $productQty = $post['product_qty'];
            $unitCase = $post['unit_case'];
            $unitQty = $post['unit_qty'];
            if (!array_key_exists('product_address', $post)) {
                $productAddress = $this->getDefaultShippingAddressId($profileId);
            } else {
                $productAddress = $post['product_address'];
            }
            $isAddition = 0;
            if (isset($post['is_addition'])) {
                $isAddition = 1;
            }
        }

        $addResult = $this->addProductsToProfile(
            $profileId,
            [
                [
                    'product_id' => $productId,
                    'qty' => $productQty,
                    'unit_case' => strtoupper($unitCase),
                    'unit_qty' => $unitQty,
                    'product_address' => $productAddress,
                ]
            ],
            $actionCache,
            $isAddition
        );

        $errorMessage = count($addResult['error_messages']) ? implode("\n", $addResult['error_messages']) : null;

        if ($isXmlHttpRequest) {
            if ($errorMessage) {
                $messageManager->addError($errorMessage);
            }

            if ($addResult['error_type']) {
                return $action->redirect($strRedirectWhenFail, ['id' => $profileId]);
            }
        }

        $result = $this->resultJson->create();

        $resultData = [
            'success' => $addResult['success']
        ];

        if ($errorMessage) {
            $resultData['message'] = $errorMessage;
        }
        /** save cache */
        $this->saveToCache($actionCache);
        return $result->setData($resultData);
    }

    /**
     * @param $profileId
     * @param array $productsData
     * @param $actionSession
     * @param $isAddition
     * @return array
     */
    public function addProductsToProfile($profileId, array $productsData, $actionSession, $isAddition)
    {
        $isSuccess = true;
        $errorType = 0;
        $errorMessages = [];

        $returnProfileLink = $profileId;

        if ($this->profileData->getTmpProfile($profileId) !== false) {
            $profileId = $this->profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }

        $versionProfileId = $this->profileRepository->getProfileIdVersion($profileId);
        if ($this->profileData->getTmpProfile($profileId) !== false) {
            $versionProfileId = $this->profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }

        $objProfileSession = $actionSession;

        try {
            $profileModel = $this->profileF->create()->load($profileId);

            if ($profileModel->getId()) {
                $customer = $this->customerRepository->getById($profileModel->getData('customer_id'));
            } else {
                throw new LocalizedException(__('Profile does not exist.'));
            }
            $defaultBillingAddressId = $this->getBillingAddressId($actionSession, $returnProfileLink);
            if ($defaultBillingAddressId == '') {
                // Case not get billing address from old product data
                $defaultBillingAddressId = $customer->getDefaultBilling();
            }
            $arrProductCartSession = $objProfileSession['product_cart'];

            if (empty($objProfileSession) || empty($objProfileSession->getData("profile_id"))) {
                throw new LocalizedException(__('Something went wrong, please reload page.'));
            }

            $stockPointDiscountRate = $this->getStockPointDiscountRateFromCartProducts($arrProductCartSession);
            $isProfileStockPoint = $this->validateStockPointProduct->checkProfileExistStockPoint($objProfileSession);

            if ($isProfileStockPoint) {
                $dataForStockPoint = $this->getDataForProductOfStockPoint($arrProductCartSession);
            }
            $productError = [];
            $productErrorMaxQty = [];
            foreach ($productsData as $productData) {
                $productId = $productData['product_id'];
                $productQty = $productData['qty'];
                $unitCase = $productData['unit_case'];
                $unitQty = $productData['unit_qty'];

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
                $productModel = $this->loadDataModel($productId);
                if ($this->validateTotalQtyProductInCart(
                    $actionSession,
                    [
                        'product_id' => $productId,
                        'qty' => $productQty,
                        'unit_case' => $unitCase,
                        'unit_qty' => $unitQty
                    ]
                )) {
                    $isSuccess = false;
                    $errorCode = 3;
                    $productError[$errorCode][] = $productModel->getName();
                    continue;
                }
                if ($this->validateProductBelongCurrentDeliveryType($actionSession, $productId) == false) {
                    $isSuccess = false;
                    $errorCode = 1;
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
                            'qty' => $productQty
                        ]
                    ];

                    $isAllow = $this->validateStockPointProduct->checkProductAllowStockPoint(
                        $objProfileSession,
                        $productModel,
                        $arrProduct
                    );

                    if (!$isAllow) {
                        $isSuccess = false;
                        $errorCode = 2;
                        $productError[$errorCode][] = $productModel->getName();
                        continue;
                    }
                }

                /** 3 Params for 3 */
                $data = $productData;
                $data['profile_id'] = $versionProfileId;
                $data['is_addition'] = $isAddition;

                // 3.1 - Group
                foreach ($arrProductCartSession as $key => $item) {
                    /**
                     * Only add product exist on profile product cart
                     */
                    if ((int)$key) {
                        $this->itemExistOnProfileCart[$item->getData('product_id')] = $item->getData('product_id');
                    }

                    if ($item->getData('profile_id') == $versionProfileId
                        && $item->getData('product_id') == $productId
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
                                $isSuccess = false;
                                $errorMessages[] = __('%1: ', $productModel->getName()).
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
                            $productErrorMaxQty = array_merge($productErrorMaxQty, $validateMaximumQty['product_errors']);
                            $productErrorMaxQty['maxQty'] = $validateMaximumQty['maxQty'];
                            $item->setData('qty', $item['qty'] - $productQty);
                            continue 2;
                        }

                        $item->setData('is_addition', ($isAddition && $item->getData('is_addition')));
                        $arrProductCartSession[$key] = $item;
                        $objProfileSession['product_cart'] = $arrProductCartSession;
                        //$actionSession->setData(Constant::SESSION_PROFILE_EDIT, $objProfileSession);

                        continue 2;
                    }
                }

                /***
                 * Validate newly added product oss for profile is not stock point
                 */
                if (!$isProfileStockPoint && !isset($this->itemExistOnProfileCart[$productId])) {
                    if (!$productModel->getIsSalable()) {
                        $isSuccess = false;
                        $errorMessages[] = __('%1: ', $productModel->getName()).
                            __('Cannot add product to this profile.');
                    }
                }

                if (!$isSuccess) {
                    continue;
                }

                $data['billing_address_id'] = $defaultBillingAddressId;
                $data['shipping_address_id'] = $productAddress;

                $data['product_type'] = $productModel->getTypeId();
                $customOptions = $this->productOption->getProductOptionCollection($productModel);
                $data['product_options'] = \Zend_Json_Encoder::encode($customOptions->getData());
                $data['parent_item_id'] = ($productModel->getParentItemId()) ? $productModel->getParentItemId() : '';

                $data['stock_point_discount_rate'] = $stockPointDiscountRate;
                if ($isProfileStockPoint) {
                    foreach ($dataForStockPoint as $field => $value) {
                        $data[$field] = $value;
                    }
                }

                $data['cart_id'] = 'new_' . $data['product_id'] . '_' . $profileId . '_' . $productAddress;
                $productCart = new DataObject($data);

                /** Validate maximum for every product*/
                $validateMaximumQty = $this->subscriptionValidator->setProfileId($profileId)
                    ->setProductCarts([$productCart])
                    ->validateMaximumQtyRestriction();
                if ($validateMaximumQty['error'] && !empty($validateMaximumQty['product_errors'])) {
                    $productErrorMaxQty = array_merge($productErrorMaxQty, $validateMaximumQty['product_errors']);
                    $productErrorMaxQty['maxQty'] = $validateMaximumQty['maxQty'];
                    continue;
                }

                $arrProductCartSession[$data['cart_id']] = $productCart;
                $objProfileSession->setData('product_cart', $arrProductCartSession);
                $objProfileSession->setData(Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED, true);
                //$actionSession->setData(Constant::SESSION_PROFILE_EDIT, $objProfileSession);

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
                $typeError = 4;
                $productError[$typeError] = $productErrorMaxQty;
                $isSuccess =false;
            }
            foreach ($productError as $type => $listName) {
                if (!empty($listName)) {
                    $name = implode(" , ", $listName);
                    switch ($type) {
                        case 1:
                            $errorMessages[$name] =
                                __('The subscription just allow to add products in the same delivery type');
                            break;
                        case 2:
                            $errorMessages[$name] =
                                __('The selected product is not allowed to buy with Stock Point.');
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
            $isSuccess = false;
            $errorType = 1;
            $errorMessages[] = $e->getMessage();
        } catch (\Exception $e) {
            $isSuccess = false;
            $errorType = 1;
            $errorMessages[] = __('Add product to profile do not successfully, please try again.');
        }

        return [
            'success' => $isSuccess,
            'error_type' => $errorType,
            'error_messages' => $errorMessages
        ];
    }

    /**
     * @param $profileCache
     * @throws LocalizedException
     */
    public function saveToCache($profileCache)
    {
        $this->profileCache->save($profileCache);
    }

    /**
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
     * @param array $cartProducts
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
     * @return string
     */
    protected function getDefaultShippingAddressId($profileId)
    {

        $profileMode = $this->profileF->create()->load($profileId);
        $arrProductCart = $profileMode->getProductCart();
        foreach ($arrProductCart as $key => $item) {
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
    protected function getBillingAddressId($actionSession, $profileId)
    {
        $arrProductCartSession = $actionSession['product_cart'];
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
     * @param $actionSession
     * @param $arrNewProduct
     *
     * @return bool
     */
    protected function validateTotalQtyProductInCart($actionSession, $arrNewProduct)
    {
        $result = false;
        $totalQty = 0;
        $arrProductCartSession = $actionSession['product_cart'];
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
     * @param $actionSession
     * @param $newProductId
     *
     * @return bool
     */
    protected function validateProductBelongCurrentDeliveryType($actionSession, $newProductId)
    {
        $arrGroupCoolNormalDm
            = [DelitypeModel::COOL, DelitypeModel::NORMAl, DelitypeModel::DM, DelitypeModel::COOL_NORMAL_DM];
        $arrProductCartSession = $actionSession['product_cart'];
        $currentDeliveryType = null;
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

        if ($actionSession->getData("stock_point_profile_bucket_id")) {
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
     * @return string
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
     * @return mixed
     */
    public function loadDataModel($productId)
    {
        return $this->productF->create()->load($productId);
    }
}
