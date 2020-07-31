<?php
/**
 * GoogleTagManager Module
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\GoogleTagManager
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\GoogleTagManager\Helper;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Pricing\Adjustment\CalculatorInterface;
use Magento\Framework\Registry;
use Magento\Framework\Session\SessionManagerInterface;
use Riki\Catalog\Helper\Data as ProductHelper;
use Riki\Sales\Helper\Data as SalesHelper;

/**
 * Class Data
 *
 * @category  RIKI
 * @package   Riki\GoogleTagManager
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Data extends AbstractHelper
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cartItem;

    /**
     * @var CollectionFactory
     */
    protected $_categoryRepository;

    /**
     * @var CalculatorInterface
     */
    protected $_adjustmentCalculator;

    /**
     * @var ProductHelper
     */
    protected $_productHelper;

    /**
     * @var SalesHelper
     */
    protected $_salesHelper;

    /**
     * @var Context
     */
    protected $_context;

    /**
     * @var Escaper
     */
    protected $_escaper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    /**
     * Data constructor.
     * @param Context $context
     * @param Registry $registry
     * @param SessionManagerInterface $sessionManager
     * @param \Magento\Checkout\Model\Cart $cartItem
     * @param CollectionFactory $categoryRepository
     * @param CalculatorInterface $calculator
     * @param ProductHelper $helper
     * @param SalesHelper $salesHelper
     * @param Escaper $escaper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        SessionManagerInterface $sessionManager,
        \Magento\Checkout\Model\Cart $cartItem,
        CollectionFactory $categoryRepository,
        CalculatorInterface $calculator,
        ProductHelper $helper,
        SalesHelper $salesHelper,
        Escaper $escaper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
    ) {
        parent::__construct($context);
        $this->registry = $registry;
        $this->sessionManager = $sessionManager;
        $this->cartItem = $cartItem;
        $this->_categoryRepository = $categoryRepository;
        $this->_adjustmentCalculator = $calculator;
        $this->_productHelper = $helper;
        $this->_salesHelper = $salesHelper;
        $this->_context = $context;
        $this->_escaper = $escaper;
        $this->redirect = $redirect;
        $this->checkoutSession = $checkoutSession;
        $this->courseFactory = $courseFactory;
    }

    /**
     * Get product add to cart
     *
     * @return string
     */
    public function googleTagManagerProductsAddtocart()
    {
        $arrData = [];
        $quote = $this->checkoutSession->getQuote();
        if (!$quote) {
            return \Zend_Json::encode($arrData);
        }
        $arrData = $this->renderDataQuoteItems($quote);
        return \Zend_Json::encode($arrData);

    }

    /**
     * Get product remove
     *
     * @return string
     */
    public function GoogleTagManagerProductsToRemove()
    {
        $productsToRemove = $this->sessionManager->getData('GoogleTagManager_products_to_remove_session');
        $arrData = [];
        if ($productsToRemove != null) {
            $arrData = $productsToRemove;
        }

        return \Zend_Json::encode($arrData);


    }

    /**
     * Check product VisibleItems
     *
     * @return bool
     */
    public function checkProductAddToCart()
    {
        $itemsVisible = $this->cartItem->getQuote()->getAllItems();
        if (is_array($itemsVisible) && count($itemsVisible) > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getCategoryNames(\Magento\Catalog\Model\Product $product)
    {
        $categoryCollection = $product->getCategoryCollection();
        $categoryCollection->addFieldToSelect('name');
        if ($categoryCollection->getSize()) {
            return $categoryCollection->getFirstItem()->getName();
        }
        return '';
    }

    /**
     * @param $product
     * @return string
     */
    public function getProductPrice($product)
    {
        if ($product->getTypeId() != 'bundle') {
            $finalPrice = $product->getPriceInfo()->getPrice(\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE);
            $finalPrice = $finalPrice->getAmount()->getValue() ?: 0;
            return intval($finalPrice);
        } else {
            $price = intval($product->getPriceInfo()->getPrice('final_price')->getValue());
            return $price;
        }
    }

    /**
     * @param $product
     * @return mixed
     */
    public function getBundleMaximumPrice($product)
    {
        $maxPrice = $product->getPriceModel()->getTotalPrices($product, 'max', true, false);
        return $maxPrice;
    }

    /**
     * @param $product
     * @return mixed
     */
    public function getBundleMinimumPrice($product)
    {
        $minPrice = $product->getPriceModel()->getTotalPrices($product, 'min', true, false);
        return $minPrice;
    }

    /**
     * @param $product
     * @return int|string
     */
    public function calculatePrice($product)
    {
        /*
         *  [0] : quantity
         * [1] : CS (case), EA (piece)
         */
        $priceData = $this->_productHelper->getProductUnitInfo($product, true, true);
        $finalPrice = $this->getProductPrice($product);
        if ($priceData[1] == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
            $finalPrice = intval($finalPrice) * $priceData[0];
        }
        return [$finalPrice, $priceData[0]];
    }

    /**
     * @param $products
     * @param $productCollection
     */
    public function mergeProducts(& $products, $productCollection)
    {
        foreach ($productCollection as $_product) {
            $products[] = $_product;
        }
    }

    /**
     * @param $productsCollection
     * @param $actionField
     * @param $productData
     * @param bool $isSaleable
     */
    public function renderDataProducts($productsCollection, $actionField, & $productData, $needCheckIsSaleAble = false)
    {
        $routeName = $this->_context->getRequest()->getRouteName();
        if ($productsCollection && $routeName != 'checkout') {
            $page = $this->_context->getRequest()->getParam('p', 1);
            $limiter = $this->_context->getRequest()->getParam('product_list_limit', 16);
            $pos = (($page - 1) * $limiter) + 1;
            foreach ($productsCollection as $product) {
                if ($needCheckIsSaleAble) {
                    $isAvailable = $product->isSaleable();
                } else {
                    $isAvailable = true;
                }
                if ($isAvailable) {
                    $dimension24 = 'Spot Product Purchase';
                    $dimension40 = 'NO';
                    $dimension41 = 'NO';
                    $price = $this->getProductPrice($product);
                    $priceDisplay = number_format($price, 2, '.', '');
                    if (!$price) {
                        $dimension40 = 'YES';
                        $dimension41 = 'YES';
                    }
                    $dimension56 = ucfirst($product->getTypeId());
                    $categories = $this->getCategoryNames($product);
                    $quantity = 1;
                    $brand = '';
                    $productData[$product->getSku()] =
                        [
                            'name' => $product->getName(),
                            'sku' => $product->getSku(),
                            'id' => $product->getId(),
                            'dimension24' => $dimension24,
                            'dimension40' => $dimension40,
                            'dimension41' => $dimension41,
                            'dimension56' => ucfirst($dimension56),
                            'quantity' => $quantity,
                            'category' => $categories,
                            'brand' => $brand,
                            'price' => $priceDisplay,
                            'position' => $pos,
                            'actionfield' => $actionField
                        ];

                    $pos++;
                }
            }
        }

    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param $actionField
     * @param $productData
     */
    public function renderDataProductsFromOrder(
        \Magento\Sales\Model\Order $order,
        $actionField,
        & $productData
    ) {
        $orderItems = $order->getItems();
        $subscriptionCode = $order->getRikiType() == 'SPOT' ? 0 : 1;
        if ($subscriptionCode) {
            $dimension24 = 'Subscription Product Purchase';
        } else {
            $dimension24 = 'Spot Product Purchase';
        }
        $pos = 1;
        foreach ($orderItems as $item) {
            $dimension40 = 'NO';
            $dimension41 = 'NO';
            if (($item->getPrice() <= 0 || $item->getFreeOfCharge() == 1) && !$item->getParentId()) { //free product
                // do not to display
            } else {
                $product = $item->getProduct();
                $categories = $this->getCategoryNames($product);
                $priceDisplay = intval($item->getPriceInclTax());
                if ($item->getUnitCase() == 'CS') {
                    $priceDisplay = $priceDisplay * $item->getQtyOrdered();
                }
                if ($this->_salesHelper->isSpotFreeGift($item)) {
                    $dimension40 = 'YES';
                }
                if ($this->_salesHelper->isFreeGift($item)) {
                    $dimension41 = 'YES';
                }
                if ($item->getUnitCase()) {
                    $quantity = intval($item->getQtyOrdered() / $item->getUnitQty());
                }

                $dimension56 = $item->getProductType();
                $brand = '';
                $productData[$product->getSku()] = [
                    'name' => $item->getName(),
                    'sku' => $product->getSku(),
                    'id' => $item->getProductId(),
                    'dimension24' => $dimension24,
                    'dimension40' => $dimension40,
                    'dimension41' => $dimension41,
                    'dimension56' => ucfirst($dimension56),
                    'price' => number_format($priceDisplay, 2, '.', ''),
                    'category' => $categories,
                    'brand' => $brand,
                    'position' => $pos,
                    'quantity' => $quantity,
                    'actionfield' => $actionField
                ];
                $pos++;
            }
        }
    }

    /**
     * @param $quoteItems
     * @param $actionField
     * @param $productData
     * @param $subscriptionCode
     */
    public function renderDataProductsFromQuote(
        $quoteItems,
        $actionField,
        & $productData,
        $subscriptionCode
    ) {
        $dimension24 = 'Spot Product Purchase';
        if ($subscriptionCode) // Subscription products
        {
            $dimension24 = 'Subscription Product Purchase';
        }
        $pos = 1;
        foreach ($quoteItems as $item) {
            $dimension40 = 'NO';
            $dimension41 = 'NO';
            $dimension56 = 'Simple';
            $price = intval($item->getPriceInclTax());
            if ($item->getUnitCase() == 'CS') {
                $price = $price * $item->getUnitQty();
            }
            $priceDisplay = number_format(intval($price), 2, '.', '');
            $quantity = intval($item->getQty() / $item->getUnitQty());
            if ($item->getData('ampromo_rule_id')) {
                $dimension40 = 'YES';
            }
            if ($item->getData('is_riki_machine')) {
                $dimension41 = 'YES';
            }
            $dimension56 = $item->getProductType();
            $brand = '';
            $product = $item->getProduct();
            $categories = $this->getCategoryNames($product);
            $productData[$product->getSku()] = [
                'name' => $item->getName(),
                'sku' => $product->getSku(),
                'id' => $item->getProductId(),
                'dimension24' => $dimension24,
                'dimension40' => $dimension40,
                'dimension41' => $dimension41,
                'dimension56' => ucfirst($dimension56),
                'price' => $priceDisplay,
                'category' => $categories,
                'brand' => $brand,
                'position' => $pos,
                'quantity' => $quantity,
                'actionfield' => $actionField
            ];
            $pos++;
        }
    }

    /**
     * @param $datas
     * @return array
     */
    public function getProductClickData($datas, $variant = null)
    {
        $jsonData = array();
        foreach ($datas as $data) {
            $tempArr = [
                "name" => $data['name'],
                "id" => $data['sku'],
                "dimension24" => $data['dimension24'],
                "dimension40" => $data['dimension40'],
                "dimension41" => $data['dimension41'],
                "dimension56" => $data['dimension56'],
                "quantity" => $data['quantity'],
                "price" => $data['price'],
                "category" => $data['category'],
                "brand" => $data['brand'],
                "position" => $data['position'],
                "actionfield" => $data['actionfield'],
                "variant" => $variant
            ];
            $jsonData[$data['id']] = \Zend_Json::encode($tempArr);
        }
        return $jsonData;
    }

    /**
     * @param $datas
     * @return array
     */
    public function getProductScrollData($datas, $variant = null)
    {
        $jsonData = array();
        foreach ($datas as $data) {
            $tempArr = [
                "name" => $data['name'],
                "id" => $data['sku'],
                "dimension24" => $data['dimension24'],
                "dimension40" => $data['dimension40'],
                "dimension41" => $data['dimension41'],
                "dimension56" => $data['dimension56'],
                "quantity" => $data['quantity'],
                "price" => $data['price'],
                "category" => $data['category'],
                "brand" => $data['brand'],
                "position" => $data['position'],
                "list" => $data['actionfield'],
                "variant" => $variant
            ];
            $jsonData[$data['sku']] = \Zend_Json::encode($tempArr);
        }
        return $jsonData;
    }


    /**
     * @param $quoteItems
     * @return array
     */
    public function renderDataQuoteItems($quoteItems)
    {

        $dimension24 = 'Spot Product Purchase';
        $variant = '';
        if ($quoteItems->getData('riki_course_id') != null) {
            $dimension24 = 'Subscription Product Purchase';
            $variant = $this->getCodeProfileSubscription($quoteItems);
        }

        $productData = [];
        $this->registry->register('skip_validate_by_oos_order_generating', true);
        foreach ($quoteItems->getAllVisibleItems() as $item) {
            $dimension40 = 'NO';
            $dimension41 = 'NO';
            $dimension60 = $item->getProductType();

            $price = intval($item->getPriceInclTax());
            if ($item->getUnitCase() == 'CS') {
                $price = $price * $item->getUnitQty();
            }

            $priceDisplay = number_format(intval($price), 2, '.', '');
            $quantity = intval($item->getQty() / $item->getUnitQty());
            if ($item->getData('ampromo_rule_id')) {
                $dimension40 = 'YES';
            }
            if ($item->getData('is_riki_machine')) {
                $dimension41 = 'YES';
            }

            $product = $item->getProduct();
            $categories = $this->getCategoryNames($product);
            $productData[$item->getProductId()] = [
                'name' => $item->getName(),
                'id' => $product->getSku(),
                'dimension24' => $dimension24,
                'dimension40' => $dimension40,
                'dimension41' => $dimension41,
                'dimension56' => ucfirst($dimension60),
                'quantity' => $quantity,
                'price' => $priceDisplay,
                'category' => $categories,
                'brand' => '',
                'variant' => $variant
            ];
        }
        $this->registry->unregister('skip_validate_by_oos_order_generating');

        return $productData;
    }

    public function getDataLayer($product)
    {
        $productData = [];
        $dimension24 = 'Spot Product Purchase';
        $dimension40 = 'NO';
        $dimension41 = 'NO';
        $price = $this->getProductPrice($product);
        $priceDisplay = number_format($price, 2, '.', '');
        if (!$price) {
            $dimension40 = 'YES';
            $dimension41 = 'YES';
        }
        $dimension60 = ucfirst($product->getTypeId());
        $categories = $this->getCategoryNames($product);
        $quantity = 1;
        $brand = '';
        $productData['name'] = addslashes($product->getName());
        $productData['sku'] = $product->getSku();
        $productData['id'] = $product->getId();
        $productData['dimension24'] = $dimension24;
        $productData['dimension40'] = $dimension40;
        $productData['dimension41'] = $dimension41;
        $productData['dimension56'] = ucfirst($dimension60);
        $productData['quantity'] = $quantity;
        $productData['category'] = $categories;
        $productData['brand'] = $brand;
        $productData['price'] = $priceDisplay;

        return $productData;
    }

    /**
     * Get product add to cart
     *
     * @return string
     */
    public function googleTagManagerProductsAddtocartOneTimes()
    {
        $quote = $this->checkoutSession->getQuote();
        $arrData = [];
        if (!$quote || count($quote->getAllItems()) == 0) {
            $this->resetProductAdded();
            return \Zend_Json::encode($arrData);
        }
        $productsToAdd = $this->sessionManager->getData('GoogleTagManager_products_addtocart_session_onetimes');
        $productsToAddQty = $this->sessionManager->getData('GoogleTagManager_products_addtocart_session_qty');
        $lastValueQty = $this->sessionManager->getData('GoogleTagManager_products_addtocart_onetimes_last_value');
        if (!$lastValueQty) {
            $lastValueQty = [];
        }

        if ($productsToAddQty) {
            $arrData = $this->_renderAddedItemsQty($productsToAddQty, $quote);

        } else {
            $arrData = $this->_renderAddedItems($productsToAdd, $quote, $lastValueQty);
        }
        $this->resetProductAdded();
        return \Zend_Json::encode($arrData);
    }

    /**
     * @param $quoteItems
     * @param $quote
     * @return array
     */
    protected function _renderAddedItems($quoteItems, $quote, $lastValues = [])
    {

        $productData = [];
        if (!$quoteItems || !$quote) {
            return $productData;
        }
        $subscriptionCode = $this->getCodeProfileSubscription($quote);
        $redirectUrl = $this->redirect->getRedirectUrl();
        $dimension24 = 'Spot Product Purchase';
        if ($subscriptionCode) {
            $dimension24 = 'Subscription Product Purchase';
        }
        //$lastValues =[];
        $currentQuoId = $quote->getId();
        foreach ($quote->getAllItems() as $item) {
            $id = $item->getProductId();
            $productAdded = $productAdded = $item->getProductType() . '_' . $id;
            if (!$item->getParentItem() && ($item->getPrice() > 0 ||
                    $item->getFreeOfCharge() != null) && in_array($productAdded, $quoteItems)) {
                $id = $item->getProductId();
                $parentQty = 1;
                $price = intval($item->getPriceInclTax());
                switch ($item->getProductType()) {
                    case 'configurable':
                        break;
                    case 'bundle':
                        $id = $item->getId() . '-' . $item->getProductId();
                        $oldQty = (array_key_exists($id, $lastValues)) ? $lastValues[$id] : 0;
                        $finalQty = ($parentQty * $item->getQty()) - $oldQty;
                        break;
                    case 'grouped':
                        $id = $item->getOptionByCode('product_type')->getProductId() . '-'
                            . $item->getProductId();

                    default:
                        if ($item->getParentItem()) {
                            $parentQty = $item->getParentItem()->getQty();
                            $id = $item->getId() . '-' .
                                $item->getParentItem()->getProductId() . '-' .
                                $item->getProductId();

                            if ($item->getParentItem()->getProductType() == 'configurable') {
                                $price = $item->getParentItem()->getProduct()->getPrice();
                            }
                        }
                        if ($item->getProductType() == 'giftcard') {
                            $price = $item->getProduct()->getFinalPrice();
                        }

                        $oldQty = (array_key_exists($id, $lastValues)) ? $lastValues[$id] : 0;
                        $finalQty = ($parentQty * $item->getQty()) - $oldQty;
                }
                $dimension40 = 'NO';
                $dimension41 = 'NO';
                $dimension60 = $item->getProductType();
                if ($item->getUnitCase() == 'CS') {
                    $price = $price * $item->getUnitQty();
                }
                $priceDisplay = number_format(intval($price), 2, '.', '');
                $quantity = intval($finalQty / $item->getUnitQty());
                if ($item->getData('ampromo_rule_id')) {
                    $dimension40 = 'YES';
                }
                if ($item->getData('is_riki_machine')) {
                    $dimension41 = 'YES';
                }
                $product = $item->getProduct();
                $categories = $this->getCategoryNames($product);
                $productData[$item->getProductId()] = [
                    'name' => $item->getName(),
                    'id' => $product->getId(),
                    'sku' => $product->getSku(),
                    'dimension24' => $dimension24,
                    'dimension40' => $dimension40,
                    'dimension41' => $dimension41,
                    'dimension56' => ucfirst($dimension60),
                    'quantity' => $quantity,
                    'price' => $priceDisplay,
                    'category' => $categories,
                    'brand' => '',
                    'variant' => $subscriptionCode,
                    'pageProductWasAdded' => $redirectUrl
                ];
            }
        }
        return $productData;
    }

    /**
     * @param $quoteItems
     * @param $quote
     * @return array
     */
    protected function _renderAddedItemsQty($items, $quoteItems)
    {

        $productData = [];
        if (!$quoteItems || !$items) {
            return $productData;
        }
        $subscriptionCode = $this->getCodeProfileSubscription($quoteItems);
        $redirectUrl = $this->redirect->getRedirectUrl();
        $dimension24 = 'Spot Product Purchase';

        if ($subscriptionCode) {
            $dimension24 = 'Subscription Product Purchase';
        }


        foreach ($quoteItems->getAllItems() as $item) {
            if (isset($items[$item->getId()]) &&
                intval($item->getQty() - $items[$item->getId()]) > 0) {
                $dimension40 = 'NO';
                $dimension41 = 'NO';
                $dimension60 = $item->getProductType();

                $price = intval($item->getPriceInclTax());
                if ($item->getUnitCase() == 'CS') {
                    $price = $price * $item->getUnitQty();
                }
                $priceDisplay = number_format(intval($price), 2, '.', '');
                $quantityChange = intval($item->getQty() - $items[$item->getId()]);
                $quantity = intval($quantityChange / $item->getUnitQty());
                if ($item->getData('ampromo_rule_id')) {
                    $dimension40 = 'YES';
                }
                if ($item->getData('is_riki_machine')) {
                    $dimension41 = 'YES';
                }
                $product = $item->getProduct();
                $categories = $this->getCategoryNames($product);
                $productData[$item->getProductId()] = [
                    'name' => $item->getName(),
                    'id' => $product->getId(),
                    'sku' => $product->getSku(),
                    'dimension24' => $dimension24,
                    'dimension40' => $dimension40,
                    'dimension41' => $dimension41,
                    'dimension56' => ucfirst($dimension60),
                    'quantity' => $quantity,
                    'price' => $priceDisplay,
                    'category' => $categories,
                    'brand' => '',
                    'variant' => $subscriptionCode,
                    'pageProductWasAdded' => $redirectUrl
                ];
            }

        }
        return $productData;
    }


    /**
     * @return mixed
     */
    public function resetProductAdded()
    {
        $this->sessionManager->setData('GoogleTagManager_products_addtocart_session_qty', '');
        $this->sessionManager->setData('GoogleTagManager_products_addtocart_session_onetimes', '');
        $this->sessionManager->setData('GoogleTagManager_products_addtocart_onetimes_last_value', null);
        return true;
    }

    /**
     * @return mixed|string
     */
    public function getCodeProfileSubscription($quote)
    {
        if ($quote->hasData('riki_course_id') && $quote->getData('riki_course_id')) {
            $courseId = $quote->getData('riki_course_id');
            $course = $this->courseFactory->create()->load($courseId);
            if ($course->getId()) {
                return $course->getData('course_code');
            }
        }
        return '';
    }


    /**
     * Get quote data
     *
     * @return \Magento\Quote\Model\Quote|string
     */
    public function getQuoteData()
    {
        $quote = $this->checkoutSession->getQuote();
        if (!$quote) {
            return '';
        }
        return $quote;
    }
}