<?php

namespace Riki\Checkout\Controller\Sidebar;

use Magento\Checkout\Model\Sidebar;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use \Magento\Framework\App\ObjectManager;
use Magento\Checkout\Model\Cart;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;
use Riki\BackOrder\Helper\Data as BackOrderData;
use Riki\AdvancedInventory\Model\Stock as AdvancedInventoryStock;

class UpdateItemQtyCustom extends Action
{
    /**
     * @var Sidebar
     */
    protected $sidebar;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * @var Cart
     */
    protected $cart;

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
     * @var \Riki\Catalog\Helper\Data
     */
    protected $catalogHelper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Riki\SubscriptionCourse\Helper\ValidateDelayPayment
     */
    protected $helperDelayPayment;

    /**
     * @var \Riki\Catalog\Model\StockState
     */
    protected $stockState;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * UpdateItemQtyCustom constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface
     * @param \Riki\Catalog\Helper\Data $catalogHelper
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepositoryInterface
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\SubscriptionCourse\Helper\Data $subCourseHelper
     * @param Cart $cart
     * @param Context $context
     * @param Sidebar $sidebar
     * @param LoggerInterface $logger
     * @param Data $jsonHelper
     * @param \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment
     * @param \Riki\Catalog\Model\StockState $stockState
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfigInterface,
        \Riki\Catalog\Helper\Data $catalogHelper,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepositoryInterface,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\SubscriptionCourse\Helper\Data $subCourseHelper,
        Cart $cart,
        Context $context,
        Sidebar $sidebar,
        LoggerInterface $logger,
        Data $jsonHelper,
        \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment,
        \Riki\Catalog\Model\StockState $stockState,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
    ) {
        $this->helperDelayPayment = $helperDelayPayment;
        $this->scopeConfig = $scopeConfigInterface;
        $this->catalogHelper = $catalogHelper;
        $this->urlInterface = $context->getUrl();
        $this->categoryRepository = $categoryRepositoryInterface;
        $this->subCourseModelFactory = $courseFactory;
        $this->subCourseHelperData = $subCourseHelper;
        $this->cart = $cart;
        $this->sidebar = $sidebar;
        $this->logger = $logger;
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
        $isCase = '';
        $itemId = (int)$this->getRequest()->getParam('item_id');
        $itemQty = (int)$this->getRequest()->getParam('item_qty');
        $quote = $this->cart->getQuote();
        $arrProductInfoFromQuote = $this->getAllProductInQuote($quote);
        $itemChange = $this->cart->getQuote()->getItemById($itemId);
        if (!$itemChange) {
            return $this->jsonResponse(__('We can\'t update the shopping cart.'));
        }

        $currentQty = $itemChange->getQty();

        if (strtoupper($itemChange->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
            $isCase = 'case';

            if ($itemChange->getUnitQty()) {
                $currentQty = $currentQty / $itemChange->getUnitQty();
            }
        }

        $totalQtyInFo = $this->calculateTotalQtyInFo(['item_id' => $itemId, 'qty' => $itemQty]);
        $maximumOrderQtyConfig = $this->getConfig(AdvancedInventoryStock::ADVANCED_INVENTORY_MAXIMUM_CART_STOCK);
        if ($maximumOrderQtyConfig > 0 && $maximumOrderQtyConfig < $totalQtyInFo) {
            $messageError = sprintf(__('Please limit the total number of items you order at one time to %s pieces or less.'), $maximumOrderQtyConfig);
            return $this->jsonErrorWhenUpdateQty($messageError,$itemId, $this->getQtyShow($itemChange), $isCase);
        }

        if (array_key_exists($itemId, $arrProductInfoFromQuote)) {
            $arrProductInfo = $arrProductInfoFromQuote[$itemId];

            /*validate stock again, before update quote item*/
            $canAssigned = $this->stockState->canAssigned(
                $arrProductInfo['product'],
                $itemQty,
                $this->stockState->getPlaceIds()
            );

            if (!$canAssigned) {
                $messageError = sprintf(
                    __("We don't have as many \"%s\" as you requested."),
                    $arrProductInfo['product_name']
                );

                return $this->jsonErrorWhenUpdateQty($messageError, $itemId, $this->getQtyShow($itemChange), $isCase);
            }
        }

        if ($quote->getRikiCourseId()) {
            $result = $this->validateSubscriptionRule($itemId, $itemQty, $quote);
            $arrCategoryIdQtyConfig = $this->arrCategoryIdQty($quote->getRikiCourseId());
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
            $objCourse = $this->subCourseModelFactory->create()->load($quote->getRikiCourseId());
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
                    $messageError =__("You need to purchase items of %1",$categoryName);
                    return $this->jsonErrorWhenUpdateQty(
                        $messageError, $itemId, $this->getQtyShow($itemChange), $isCase);
                    break;
                case 4:
                    $messageError = __("In %1, the total number of items in the shopping cart have at least %2 quantity",
                        $objCourseName,$objCourse->getData('minimum_order_qty'));
                    $this->sidebar->checkQuoteItem($itemId);
                    return $this->jsonErrorWhenUpdateQty($messageError, $itemId, $this->getQtyShow($itemChange), $isCase);
                    break;
                case 5:
                    $messageError = __("You need to purchase items of %1",$categoryName);
                    $this->sidebar->checkQuoteItem($itemId);
                    return $this->jsonErrorWhenUpdateQty($messageError, $itemId, $this->getQtyShow($itemChange), $isCase);
                    break;
                default:
                    // Do nothing
            }

            /** Validate maximum qty restriction */
            if (!isset($arrProductInfoFromQuote[$itemId]['product'])) {
                $messageError = __("We can't find the quote item.");
                return $this->jsonErrorWhenUpdateQty($messageError, $itemId, $this->getQtyShow($itemChange), $isCase);
            }

            if ($quote->getData('riki_course_id')) {
                $productId = $arrProductInfoFromQuote[$itemId]['product_id'];
                $arrProductInfoFromQuote[$itemId]['qty'] = $itemQty;
                $prepareData = $this->subscriptionValidator->prepareProductData([$productId => $arrProductInfoFromQuote[$itemId]]);
                $validateMaximumQty = $this->subscriptionValidator
                    ->setCourseId($quote->getData('riki_course_id'))
                    ->setProductCarts($prepareData)
                    ->validateMaximumQtyRestriction();

                if ($validateMaximumQty['error']) {
                    $message = $this->subscriptionValidator->getMessageMaximumError(
                        $validateMaximumQty['product_errors'],
                        $validateMaximumQty['maxQty']
                    );
                    return $this->jsonErrorWhenUpdateQty($message, $itemId, $this->getQtyShow($itemChange), $isCase);
                }
            }
        }

        try {
            if (!$this->getFormKeyValidator()->validate($this->getRequest())) {
                throw new LocalizedException(__('We can\'t update the shopping cart.'));
            }
            $this->sidebar->checkQuoteItem($itemId);
            $this->sidebar->updateQuoteItem($itemId, $itemQty);
            return $this->jsonResponse();
        } catch (LocalizedException $e) {
            return $this->jsonErrorWhenUpdateQty($e->getMessage(),$itemId, $currentQty, $isCase);
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonErrorWhenUpdateQty($e->getMessage(),$itemId, $currentQty, $isCase);
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
     * Calculate Total Qty Info
     *
     * @param $arrChangeItemInfo
     *
     * @return int
     */
    public function calculateTotalQtyInFo($arrChangeItemInfo)
    {
        /* @var $quote \Magento\Quote\Model\Quote */
        $totalQty = 0;
        $quote = $this->cart->getQuote();
        $items = $quote->getAllItems();
        if (!empty($items)) {
            foreach ($items as $item) {
                list($unitQty,$caseDisplay) = $this->catalogHelper->getProductUnitInfo($item->getProduct()->getId());
                if ($arrChangeItemInfo['item_id'] == $item->getId()) {
                    if (strtoupper($caseDisplay)
                        == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                        $totalQty = $totalQty + ($arrChangeItemInfo['qty'] / $unitQty);
                    }else {
                        $totalQty = $totalQty + $arrChangeItemInfo['qty'];
                    }
                } else {
                    if (strtoupper($caseDisplay)
                        == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                        $totalQty = $totalQty + ($item->getQty() / $unitQty);
                    }else {
                        $totalQty = $totalQty + $item->getQty();
                    }
                }
            }
        }

        return $totalQty;
    }

    /**
     * Prepare layout for validate back order
     *
     * @param $arrProductFromQuote
     * @param $itemIdChange
     * @param $itemQtyChange
     *
     * @return array
     */
    public function prepareDataForValidateBackOrder($arrProductFromQuote, $itemIdChange, $itemQtyChange)
    {
        $arrResult = array();
        foreach ($arrProductFromQuote as $itemId => $productInfo) {
            if ($itemId == $itemIdChange) {
                $arrResult[$productInfo['product_id']]['qty'] = $itemQtyChange;
            }else {
                $arrResult[$productInfo['product_id']]['qty'] = $productInfo['qty'];
            }
            $arrResult[$productInfo['product_id']]['product'] = $productInfo['product'];
            $arrResult[$productInfo['product_id']]['assignation'] = '';
        }
        return $arrResult;
    }
    public function getQtyShow($item)
    {
        if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE) {
            return $item->getQty();
        }

        if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
            return ($item->getQty() / $item->getUnitQty());
        }
    }

    public function arrCategoryIdQty($courseId)
    {
        $objCourse = $this->subCourseModelFactory->create()->load($courseId);
        $mustHaveCatId = $objCourse->getData("must_select_sku");
        return explode(':', $mustHaveCatId);
    }


    /**
     * @param $quote
     *
     * @return array
     */
    public function getAllProductInQuote($quote)
    {
        /* @var \Magento\Quote\Model\Quote $quote */
        $arrResult = array();
        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            return $arrResult;
        }
        /* @var $quote \Magento\Quote\Model\Quote */
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
                $arrResult[$item->getId()]['qty'] = $item->getQty();
                $arrResult[$item->getId()]['product_name'] = $product->getName();
                $arrResult[$item->getId()]['assignation'] = '';
                $arrResult[$item->getId()]['case_display'] = $item->getUnitCase();
                $arrResult[$item->getId()]['unit_qty'] = $item->getUnitQty();
            }
        }
        return $arrResult;
    }

    /**
     * Validate subscription rule when edit product
     *
     * @param $itemId
     * @param $itemQty
     * @param $quote
     *
     * @return bool
     */
    public function validateSubscriptionRule($itemIdUpdate, $itemQtyUpdate, $quote)
    {
        /* @var $quote \Magento\Quote\Model\Quote */
        $objCourse = $this->subCourseModelFactory->create()->load($quote->getRikiCourseId());
        $arrProductId = [];
        $arrProductIdQty = [];
        $totalQtyShow = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            $buyRequest = $item->getBuyRequest();
            if (isset($buyRequest['options']['ampromo_rule_id'])) {
                continue;
            }
            if ($item->getData('is_riki_machine')) {
                continue;
            }

            if ($item->getId() != $itemIdUpdate) {
                if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                    $unitQty = ((int)$item->getUnitQty() != 0)?(int)$item->getUnitQty():1;
                    $arrProductIdQty[$item->getProduct()->getId()] = $item->getQty()/$unitQty;
                }
                else{
                    $arrProductIdQty[$item->getProduct()->getId()] = $item->getQty();
                }
            }
            else{
                if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                    $unitQty = ((int)$item->getUnitQty() != 0)?(int)$item->getUnitQty():1;
                    $arrProductIdQty[$item->getProduct()->getId()] = $itemQtyUpdate/$unitQty;
                }
                else{
                    $arrProductIdQty[$item->getProduct()->getId()] = $itemQtyUpdate;
                }
            }
            if ($item->getId() == $itemIdUpdate && $itemIdUpdate == 0) {
                continue;
            }

            $arrProductId[] = $item->getProduct()->getId();

            if ($item->getId() != $itemIdUpdate) {
                if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE) {
                    $totalQtyShow = $totalQtyShow + $item->getQty();
                }

                if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                    $totalQtyShow = $totalQtyShow + ($item->getQty() / $item->getUnitQty());
                }
            } else {
                if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE) {
                    $totalQtyShow = $totalQtyShow + $itemQtyUpdate;
                }

                if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                    $totalQtyShow = $totalQtyShow + ($itemQtyUpdate/ $item->getUnitQty());
                }
            }
        }

        $mustHaveCatId = $objCourse->getData('must_select_sku');
        $isValid = $this->subCourseHelperData->isValidMakeHaveInCart($arrProductId, $mustHaveCatId);
        if (!$isValid) {
            return 3;
        }

        $minimumOrderQty = $objCourse->getData('minimum_order_qty');
        if( $totalQtyShow < $minimumOrderQty ) {
            return 4; // Maximum limit
        }

        if (!$this->subCourseHelperData->isValidMustHaveQtyInCategory($arrProductIdQty, $mustHaveCatId)) {
            return 5; // Minimum product qty in category
        }

        return 0;
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

    protected function jsonErrorWhenUpdateQty($error, $itemId, $itemValue, $isCase = '')
    {
        $response = [
            'success' => false,
            'error_message' => $error,
            'type' => 'updateQty',
            'itemId' => $itemId,
            'itemValue' => $itemValue,
            'is_case' => $isCase
        ];
        return $this->getResponse()->representJson( $this->jsonHelper->jsonEncode($response));
    }

    /**
     * Getter for FormKeyValidator
     *
     * @deprecated
     * @return Validator
     */
    private function getFormKeyValidator()
    {
        if ($this->formKeyValidator === null) {
            $this->formKeyValidator = ObjectManager::getInstance()->get(Validator::class);
        }
        return $this->formKeyValidator;
    }
}
