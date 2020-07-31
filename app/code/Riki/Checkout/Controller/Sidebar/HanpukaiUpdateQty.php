<?php

namespace Riki\Checkout\Controller\Sidebar;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data;
use Magento\Checkout\Model\Sidebar;
use Psr\Log\LoggerInterface;
use Riki\Subscription\Model\Constant;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;
use Riki\AdvancedInventory\Model\Stock as AdvancedInventoryStock;

class HanpukaiUpdateQty extends Action
{
    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @var Sidebar
     */
    protected $sidebar;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Riki\Checkout\Helper\Data
     */
    protected $rikiCheckoutHelperData;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $resolverInterface;

    /**
     * @var \Riki\SubscriptionCourse\Helper\Data
     */
    protected $subCourseHelperData;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $subCourseModelFactory;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Riki\Catalog\Helper\Data
     */
    protected $catalogHelper;

    protected $productErrors = null;

    protected $maximumOrderQty = null;

    /**
     * @var \Riki\Catalog\Model\StockState
     */
    protected $stockState;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * HanpukaiUpdateQty constructor.
     * @param \Riki\Catalog\Helper\Data $catalogHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param BackOrderData $backOrderHelper
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepositoryInterface
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\SubscriptionCourse\Helper\Data $courseHelperData
     * @param \Magento\Framework\Locale\ResolverInterface $resolverInterface
     * @param \Riki\Checkout\Helper\Data $rikiCheckoutHelperData
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param Sidebar $sidebar
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Data $jsonHelper
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Riki\Catalog\Helper\Data $catalogHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepositoryInterface,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\SubscriptionCourse\Helper\Data $courseHelperData,
        \Magento\Framework\Locale\ResolverInterface $resolverInterface,
        \Riki\Checkout\Helper\Data $rikiCheckoutHelperData,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Checkout\Model\Session $checkoutSession,
        Sidebar $sidebar,
        Context $context,
        LoggerInterface $logger,
        Data $jsonHelper,
        \Riki\Catalog\Model\StockState $stockState,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
    ) {
        $this->catalogHelper = $catalogHelper;
        $this->scopeConfig = $scopeConfigInterface;
        $this->urlInterface = $context->getUrl();
        $this->categoryRepository = $categoryRepositoryInterface;
        $this->subCourseModelFactory = $courseFactory;
        $this->subCourseHelperData = $courseHelperData;
        $this->resolverInterface = $resolverInterface;
        $this->rikiCheckoutHelperData = $rikiCheckoutHelperData;
        $this->cart = $cart;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->sidebar = $sidebar;
        $this->jsonHelper = $jsonHelper;
        $this->stockState = $stockState;
        $this->subscriptionValidator = $subscriptionValidator;
        parent::__construct($context);
    }

    /**
     * Executes the main action of the controller
     *
     * @return $this
     */
    public function execute()
    {
        $hanpukaiChangeAllQty = (int)$this->getRequest()->getParam('hanpukai_change_all_qty');
        $quote = $this->checkoutSession->getQuote();
        $courseId = $quote->getData('riki_course_id');
        $configHanpukaiProduct = $this->rikiCheckoutHelperData->getArrProductFirstDeliveryHanpukai($courseId);

        $quoteItemData = $this->prepareDataForValidateStock($quote, $hanpukaiChangeAllQty);

        $totalQtyInFo = $this->calculateTotalQtyFo($hanpukaiChangeAllQty);
        $maximumOrderQtyConfig = $this->getConfig(AdvancedInventoryStock::ADVANCED_INVENTORY_MAXIMUM_CART_STOCK);
        if ($maximumOrderQtyConfig > 0 && $maximumOrderQtyConfig < $totalQtyInFo) {
            $messageError = sprintf(__('Please limit the total number of items you order at one time to %s pieces or less.'), $maximumOrderQtyConfig);
            return $this->jsonErrorWhenUpdateQty($messageError, $quote->getData(Constant::RIKI_HANPUKAI_QTY));
        }

        /*validate stock again before update quote item*/
        foreach ($quoteItemData as $productId => $productInfo) {
            $canAssigned = $this->stockState->canAssigned(
                $productInfo['product'],
                $productInfo['qty'],
                $this->stockState->getPlaceIds()
            );

            if (!$canAssigned) {
                $messageError = sprintf(
                    __("We don't have as many \"%s\" as you requested."),
                    $productInfo['product_name']
                );

                return $this->jsonErrorWhenUpdateQty($messageError, $quote->getData(Constant::RIKI_HANPUKAI_QTY));
            }
        }

        /**
         * Check subscription rule
         */
        $result = $this->validateWhenUpdateQtyItemInCheckout($configHanpukaiProduct, $hanpukaiChangeAllQty, $courseId, $quote);
        $arrCategoryIdQtyConfig = $this->arrCategoryIdQty($courseId);
        if (count($arrCategoryIdQtyConfig) > 1) {
            $categoryId = $arrCategoryIdQtyConfig[0];
            if ($categoryObj = $this->categoryRepository->get($categoryId)) {
                $categoryName = $categoryObj->getName();
            } else {
                $categoryName = '';
            }
            $qtyOfCategory = $arrCategoryIdQtyConfig[1];
        } else {
            $categoryName = '';
            $qtyOfCategory = 0;
        }
        $objCourse = $this->subCourseModelFactory->create()->load($courseId);
        $objCourseName = $objCourse->getData('course_name');
        if ($objCourse->getSubscriptionType() == SubscriptionType::TYPE_SUBSCRIPTION) {
            $linkBackToSubPage = $this->urlInterface->getUrl('subscription/course/view/',
                ['code' => $objCourse->getData('course_code')]);
        } else {
            $linkBackToSubPage = $this->urlInterface->getUrl('subscription/hanpukai/view/',
                ['code' => $objCourse->getData('course_code')]);
        }
        switch ($result) {
            case 3:
                $messageError =
                    __("You must select at least %1 quantity product(s) belong to \"%2\" category in %3", $qtyOfCategory, $categoryName, $objCourseName);
                return $this->jsonErrorWhenUpdateQty($messageError, $quote->getData(Constant::RIKI_HANPUKAI_QTY));
                break;
            case 4:
                $messageError = sprintf(__("With this subscription order, it is necessary to purchase more than %s in total.Sorry, Please change the order quantity and proceed to the next."),$objCourse->getData('minimum_order_qty'));
                return $this->jsonErrorWhenUpdateQty($messageError, $quote->getData(Constant::RIKI_HANPUKAI_QTY));
                break;
            case 6:
                $message = $this->subscriptionValidator->getMessageMaximumError(
                    $this->productErrors,
                    $this->maximumOrderQty
                );
                return $this->jsonResponse($message);
            default:
                break;
                // Do nothing
        }


        try {
            $cartData = $this->rikiCheckoutHelperData->makeCartDataFromQuote($quote);
            if (is_array($cartData)) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->resolverInterface->getLocale()]
                );
                $previousFactor = $this->rikiCheckoutHelperData->calculateFactor($configHanpukaiProduct, $cartData, $quote);
                $arrMapItemIdToProductId = $this->rikiCheckoutHelperData->mapQuoteItemIdToProductId($quote);
                if ($previousFactor === false) {
                    foreach ($cartData as $index => $data) {
                        if (isset($data['qty'])) {
                            // Reset product qty of hanpukai
                            $productId = $arrMapItemIdToProductId[$index];
                            if (in_array($productId, array_keys($configHanpukaiProduct))) {
                                $cartData[$index]['qty'] = $configHanpukaiProduct[$productId]['qty'];
                            } else {
                                // Additional product may be gift product => not reset
                                $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                            }
                        }
                    }
                    $this->checkoutSession->getQuote()->setData(Constant::RIKI_HANPUKAI_QTY, 1);
                }  else {
                    foreach ($cartData as $index => $data) {
                        if (isset($data['qty'])) {
                            $productId = $arrMapItemIdToProductId[$index];
                            if (in_array($productId, array_keys($configHanpukaiProduct))) {
                                // just update qty for product allow hanpukai not gift product
                                $cartData[$index]['qty'] = ($data['qty'] / $previousFactor) * $hanpukaiChangeAllQty;
                            } else {
                                // Additional product may be gift product => not reset
                                $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                            }
                        }
                    }
                    $this->checkoutSession->getQuote()->setData(Constant::RIKI_HANPUKAI_QTY, $hanpukaiChangeAllQty);
                }

                $cartData = $this->cart->suggestItemsQty($cartData);
                $this->cart->updateItems($cartData)->save();
            }
            return $this->jsonResponse();
        } catch (LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Get Store Config
     *
     * @param $path
     *
     * @return string
     */
    public function getConfig($path)
    {

        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Calculate Total Qty Fo
     *
     * @param $quote
     * @param $hanpukaiChangeAllQty
     *
     * @return int
     */
    public function calculateTotalQtyFo($hanpukaiChangeAllQty)
    {
        $totalQty = 0;
        $quote = $this->checkoutSession->getQuote();
        $items = $quote->getAllItems();
        if (!empty($items)) {
            foreach ($items as $item) {
                list($unitQty,$caseDisplay) = $this->catalogHelper->getProductUnitInfo($item->getProduct()->getId());
                $buyRequest = $item->getBuyRequest();
                if (isset($buyRequest['options']['ampromo_rule_id']) || $item->getData('is_riki_machine')) {
                    if (strtoupper($caseDisplay)
                        == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                        $totalQty = $totalQty + $item->getQty() / $unitQty;
                    } else {
                        $totalQty  = $totalQty + $item->getQty();
                    }
                } else {
                    if (strtoupper($caseDisplay)
                        == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                        $totalQty = $totalQty + ($item->getQty() / $unitQty) * $hanpukaiChangeAllQty;
                    } else {
                        $totalQty = $totalQty + $item->getQty() * $hanpukaiChangeAllQty;
                    }
                }
            }
        }
        return $totalQty;
    }

    /**
     * Prepare data for validate back order
     *
     * @param $quote
     * @param $hanpukaiChangeAllQty
     * @return array
     */
    protected function prepareDataForValidateStock($quote, $hanpukaiChangeAllQty)
    {
        $arrResult = [];

        /** @var $quote \Magento\Quote\Model\Quote */
        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            return $arrResult;
        }

        $items = $quote->getAllItems();
        if (!empty($items)) {
            foreach ($items as $item) {
                $buyRequest = $item->getBuyRequest();
                if (isset($buyRequest['options']['ampromo_rule_id'])) {
                    continue;
                }
                if ($item->getData('is_riki_machine')) {
                    continue;
                }
                $product = $item->getProduct();
                $arrResult[$item->getId()]['product_id'] = $product->getId();
                $arrResult[$item->getId()]['product'] = $product;
                $arrResult[$item->getId()]['qty'] = $item->getQty() * $hanpukaiChangeAllQty;
                $arrResult[$item->getId()]['product_name'] = $product->getName();
                $arrResult[$item->getId()]['assignation'] = '';
            }
        }
        return $arrResult;
    }
    /**
     * Validate subscription rule
     *
     * @param $configProduct
     * @param $hanpukaiUpdateQty
     * @param $courseId
     *
     * @return int
     * @throws \Exception
     */
    public function validateWhenUpdateQtyItemInCheckout($configProduct, $hanpukaiUpdateQty, $courseId, $quote)
    {
        $totalQty = 0;
        $arrProductId = array_keys($configProduct);
        foreach ($configProduct as $productItem) {
            $totalQty = $totalQty + $productItem['qty'];
        }
        $totalQty = $totalQty * $hanpukaiUpdateQty;
        $objCourse = $this->subCourseHelperData->loadCourse($courseId);
        $mustHaveCatId = $objCourse->getData('must_select_sku');
        $isValid = $this->subCourseHelperData->isValidMakeHaveInCart($arrProductId, $mustHaveCatId);
        if (!$isValid) {
            return 3;
        }

        $minimumOrderQty = $objCourse->getData('minimum_order_qty');
        if( $totalQty < $minimumOrderQty ) {
            return 4; // Maximum limit
        }

        $items = [];
        $quoteItems = $quote->getAllVisibleItems();
        if (!empty($quoteItems)) {
            foreach ($quoteItems as $item) {
                if ($item->getData('is_riki_machine') ==1) {
                    continue;
                }
                $buyRequest = $item->getBuyRequest();
                if (isset($buyRequest['options']['ampromo_rule_id'])) {
                    continue;
                }

                $items[$item->getProductId()]['qty'] = $hanpukaiUpdateQty;
                $items[$item->getProductId()]['case_display'] = $item->getUnitCase();
                $items[$item->getProductId()]['unit_qty'] = $item->getUnitQty();
            }
        }
        /**
         * Process qty for hanpukai
         */
        if ($hanpukaiUpdateQty > 0 && is_array($configProduct)) {
            foreach ($configProduct as $id => $info) {
                $items[$id]['qty'] = $info['qty'] * $hanpukaiUpdateQty;
            }
        }

        /** Validate maximum qty restriction */
        $prepareData = $this->subscriptionValidator->prepareProductData($items);
        $validateMaximumQty = $this->subscriptionValidator
            ->setCourseId($courseId)
            ->setProductCarts($prepareData)
            ->validateMaximumQtyRestriction();

        if ($validateMaximumQty['error']) {
            $this->productErrors = $validateMaximumQty['product_errors'];
            $this->maximumOrderQty = $validateMaximumQty['maxQty'];
            return 6;
        }

        return 0;
    }

    /**
     * Json error
     *
     * @param $error
     * @param $itemId
     * @param $itemValue
     *
     * @return mixed
     */
    protected function jsonErrorWhenUpdateQty($error, $qtyValue)
    {
        $response = [
            'success' => false,
            'error_message' => $error,
            'type' => 'updateHanpukaiQty',
            'qtyValue' => $qtyValue
        ];
        return $this->getResponse()->representJson( $this->jsonHelper->jsonEncode($response));
    }

    /**
     *
     * @param $courseId
     *
     * @return array
     */
    public function arrCategoryIdQty($courseId)
    {
        $objCourse = $this->subCourseModelFactory->create()->load($courseId);
        $mustHaveCatId = $objCourse->getData("must_select_sku");
        return explode(':', $mustHaveCatId);
    }

    /**
     * Compile JSON response
     *
     * @param string $error
     * @return Http
     */
    protected function jsonResponse($error = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($this->sidebar->getResponseData($error))
        );
    }
}